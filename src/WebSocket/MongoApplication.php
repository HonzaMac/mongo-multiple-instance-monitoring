<?php


namespace MongoMonitoring\WebSocket;

use Generator;
use Icicle\Awaitable;
use Icicle\Coroutine\Coroutine;
use Icicle\Http\Message\Request;
use Icicle\Http\Message\Response;
use Icicle\Loop;
use Icicle\Socket\Socket;
use Icicle\WebSocket\Application;
use Icicle\WebSocket\Connection;
use Jmikola\React\MongoDB\Connection as MongoConnection;
use Jmikola\React\MongoDB\ConnectionFactory;
use Jmikola\React\MongoDB\Protocol\Query;
use Jmikola\React\MongoDB\Protocol\Reply;
use MongoMonitoring\Websocket\Messages;

class MongoApplication implements Application
{
    /**
     * @var array
     */
    private $ipList;
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct(ConnectionFactory $factory, $ipList)
    {
        $this->ipList = $ipList;
        $this->connectionFactory = $factory;
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param Socket $socket
     * @return Generator|Awaitable\Awaitable|Response|void
     */
    public function onHandshake(Response $response, Request $request, Socket $socket)
    {
        return $response;
    }

    /**
     * This method is called when a Server connection is established to the Server server. This method should
     * not resolve until the connection should be closed.
     *
     * @coroutine
     *
     * @param Connection $websocketConnection
     * @param Response $response
     * @param Request $request
     *
     * @return Generator|Awaitable\Awaitable|null
     */
    public function onConnection(Connection $websocketConnection, Response $response, Request $request)
    {
        while ($websocketConnection->isOpen()) {
            foreach ($this->ipList as $instanceIp) {
                $generator = $this->fetchInit($websocketConnection, $instanceIp);
                $coroutine = new Coroutine($generator);
                $coroutine->then(null, function () {
                    return 'aaaa';
                });
                yield $coroutine;
            }
            echo 'ANOTHER CYCLE' . PHP_EOL;
        }
    }


    /**
     * @param Connection $connection
     * @param string $instanceIp
     * @return Generator
     */
    public function fetchInit(Connection $websocketConnection, $instanceIp)
    {
        list($host, $port) = explode(':', $instanceIp);
        /** @var MongoConnection $connection */
        $connectionThenable = Awaitable\adapt($this->connectionFactory->create($host, $port, ['connectTimeoutMS' => 500, 'socketTimeoutMS' => 500]));
        $connectionThenable->timeout(2, function () use ($connectionThenable, $instanceIp) {
            $connectionThenable->cancel();
        });
        $connection = (yield $connectionThenable);
        echo $instanceIp . ' connected' . PHP_EOL;

        $listDatabasesQuery = new Query('admin.$cmd', ['listDatabases' => 1], null, 0 ,1);
        /** @var Reply $reply */
        $reply = (yield Awaitable\adapt($connection->send($listDatabasesQuery)));
        $listDbs = current(iterator_to_array($reply->getIterator()));
        $initResponse = Messages\Init::create($instanceIp, $listDbs);
        $hostId = $initResponse->getHostId();
        echo $instanceIp . ': getting list of databases'. PHP_EOL;
        yield $websocketConnection->send($initResponse);

        $serverStatusQuery = new Query('admin.$cmd', ['serverStatus' => 1], null, 0, 1); // this works
        $serverStatusReply = (yield Awaitable\adapt($connection->send($serverStatusQuery)));
        /** @var Reply $serverStatusReply */
        $serverStatus = current(iterator_to_array($serverStatusReply->getIterator()));
        echo $instanceIp . ': getting server stats' . PHP_EOL;
        yield $websocketConnection->send(Messages\ServerStatus::create($hostId, $serverStatus));

        $topQuery = new Query('admin.$cmd', ['top' => 1], null, 0, 1); // this works
        $topReply = (yield Awaitable\adapt($connection->send($topQuery)));
        /** @var Reply $topReply */
        $top = current(iterator_to_array($topReply->getIterator()));
        echo $instanceIp . ': getting top' . PHP_EOL;
        yield $websocketConnection->send(Messages\Top::create($hostId, $top));


        $databases = $listDbs['databases'];
        foreach ($databases as $dbId => $db) {
            $dbName = $db['name'];
            $dbStatsQuery = new Query($dbName . '.$cmd', ['dbStats' => 1], null, 0, 1);
            /** @var Reply $dbStatsReply */
            $dbStatsReply = (yield Awaitable\adapt($connection->send($dbStatsQuery)));
            $dbStats = current(iterator_to_array($dbStatsReply->getIterator()));
            echo $instanceIp . ': sending database stats for [' . $dbName . ']' . PHP_EOL;
            yield $websocketConnection->send(Messages\DbStats::create($hostId, $dbStats));
        }

        yield $connection->close();
    }
}

//  $db['stats'] = \MongoMonitoring\command($mongoDb, 'db.stats()');
//  $db['version'] = \MongoMonitoring\command($mongoDb, 'db.version()');
//  $db['hostInfo'] = \MongoMonitoring\command($mongoDb, 'db.hostInfo()');

