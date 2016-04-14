<?php


namespace MongoMonitoring\WebSocket;

use Generator;
use Icicle\Awaitable;
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
use MongoMonitoring\WebSocket\Messages\Init;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\Promise;
use React\Promise\PromiseTest\FullTestTrait;
use React\SocketClient\ConnectionException;

class MongoApplication implements Application
{
    const DELAY = 0.01; // 100ms
    const DELAY_SERVER_STATUS = 0.5; // 100ms

    /** @var array */
    private $instanceList;

    /**
     * @var array
     */
    private $ipList;
    /**
     * @var ConnectionFactory
     */
    private $factory;

    public function __construct(ConnectionFactory $factory, $ipList)
    {
        $this->ipList = $ipList;
        $this->factory = $factory;
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
        foreach ($this->ipList as $instanceIp) {
            yield $this->fetchInit($websocketConnection, $instanceIp);
        }

    }


    /**
     * @param Connection $connection
     * @param string $instanceIp
     * @return Generator
     */
    public function fetchInit(Connection &$websocketConnection, $instanceIp)
    {
        list($host, $port) = explode(':', $instanceIp);
        /** @var MongoConnection $connection */
        $connection = (yield Awaitable\adapt($this->factory->create($host, $port, ['connectTimeoutMS' => 500, 'socketTimeoutMS' => 500])));

        $listDatabasesQuery = new Query('admin.$cmd', ['listDatabases' => 1], null, 0 ,1);

        /** @var Reply $reply */
        $reply = (yield Awaitable\adapt($connection->send($listDatabasesQuery)));
        $listDbs = current(iterator_to_array($reply->getIterator()));
        $initResponse = Init::create($instanceIp, $listDbs);

        yield $websocketConnection->send($initResponse);

        $serverStatusQuery = new Query('admin.$cmd', ['serverStatus' => 1], null, 0, 1); // this works
        $reply = (yield Awaitable\adapt($connection->send($serverStatusQuery)));
        $serverStatus = current(iterator_to_array($reply->getIterator()));
        $response = Messages\ServerStatus::create($initResponse->getHostId(), $serverStatus);
        yield $websocketConnection->send($response);

        $dbStatsQuery = new Query('admin.$cmd', ['dbStats' => 1], null, 0, 1); // this works
        $reply = (yield Awaitable\adapt($connection->send($dbStatsQuery)));
        $dbStats = current(iterator_to_array($reply->getIterator()));
        $response = Messages\DbStats::create($initResponse->getHostId(), $dbStats);
        yield $websocketConnection->send($response);


        yield $connection->close();

//        $this->fetchDatabaseInfo($listDbs, $connection);
    }

    /**
     * @param $listDbs
     * @param $connection
     */
    private function fetchDatabaseInfo($listDbs, $connection)
    {
        $databases = $listDbs['databases'];
        foreach ($databases as $dbId => $db) {
            $dbName = $db['name'];
//            $connection = (yield Awaitable\adapt($this->factory->create($host, $port, ['connectTimeoutMS' => 500, 'socketTimeoutMS' => 500])));


            $selectDbQuery = new Query('admin.$cmd', ['db' => $dbName]);
            $reply = (yield Awaitable\adapt($connection->send($selectDbQuery)));
            var_dump($reply);
            $dbSelect = current(iterator_to_array($reply->getIterator()));
            var_dump($dbSelect);

//            $selectDbQuery = new Query('admin.$cmd', ['whatsmyuri'=> 1], null, 0, 1);
//            $reply = (yield Awaitable\adapt($connection->send($selectDbQuery)));
//            $dbSelect = current(iterator_to_array($reply->getIterator()));
//            var_dump($dbSelect);
//
//            $dbStatusQuery = new Query($dbName, ['dbStats' => 1], null, 0, 1); // this works
//            var_dump($dbStatusQuery);
//            $reply = (yield Awaitable\adapt($connection->send($dbStatusQuery)));
//            $dbStatus = current(iterator_to_array($reply->getIterator()));
//            var_dump($dbStatus);
//            $response = DbStatus::create($initResponse->getHostId(), $dbName, $dbStatus);
//            yield $websocketConnection->send($response);
        }
    }


}

//  $db['stats'] = \MongoMonitoring\command($mongoDb, 'db.stats()');
//  $db['version'] = \MongoMonitoring\command($mongoDb, 'db.version()');
//  $db['hostInfo'] = \MongoMonitoring\command($mongoDb, 'db.hostInfo()');

