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
    /** @var array */
    private $cache = [];

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
        $connectedInstances = [];
        foreach ($this->ipList as $instanceIp) {
            $generator = $this->fetch($websocketConnection, $instanceIp);
            list($connection, $databases) = (yield $generator);
            $connectedInstances[] = [$connection, $instanceIp, $databases];
        }
        while ($websocketConnection->isOpen()) {
            foreach ($connectedInstances as list($connection, $instanceIp, $databases)) {
                foreach ($databases as $db) {
                    yield (new Coroutine($this->fetchDbStatus($websocketConnection, $instanceIp, $db['name'], $connection)))->wait();
                }
                yield (new Coroutine($this->fetchLog($websocketConnection, $instanceIp, $connection)))->wait();
            }
            yield \Icicle\Coroutine\sleep(3);

        }
    }


    /**
     * @param Connection $connection
     * @param string $instanceIp
     * @return Generator
     */
    public function fetch(Connection $websocketConnection, $instanceIp)
    {
        list($host, $port) = explode(':', $instanceIp);
        $connection = (yield $this->connectToHost($instanceIp, $host, $port));
        $listDbs = (yield $this->fetchListDatabase($websocketConnection, $instanceIp, $connection));

        yield $this->fetchBuildInfo($websocketConnection, $instanceIp, $connection);
        yield $this->fetchHostInfo($websocketConnection, $instanceIp, $connection);
        yield $this->fetchServerStatus($websocketConnection, $instanceIp, $connection);
        yield $this->fetchTop($websocketConnection, $instanceIp, $connection);

        $databases = $listDbs['databases'];
        yield [$connection, $databases];
    }

    /**
     * @param string $instanceIp
     * @param string $host
     * @param int $port
     * @return Awaitable\Awaitable|MongoConnection
     */
    private function connectToHost($instanceIp, $host, $port)
    {
        /** @var MongoConnection $connection */
        $connectionThenable = Awaitable\adapt($this->connectionFactory->create($host, $port, ['connectTimeoutMS' => 500, 'socketTimeoutMS' => 500]));
        $connectionThenable->timeout(2, function () use ($connectionThenable, $instanceIp) {
            $connectionThenable->cancel();
        });
        $connectionThenable->then(function () use ($instanceIp) {
            echo $instanceIp . ' connected' . PHP_EOL;
        });

        return $connectionThenable;
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator|mixed
     */
    private function fetchListDatabase(Connection $websocketConnection, $instanceIp, $connection)
    {
        $listDatabasesQuery = new Query('admin.$cmd', ['listDatabases' => 1], null, 0, 1);
        /** @var Reply $reply */
        $reply = (yield Awaitable\adapt($connection->send($listDatabasesQuery)));
        $listDbs = current(iterator_to_array($reply->getIterator()));
        $initResponse = Messages\Init::create($instanceIp, $instanceIp, $listDbs);
        echo $instanceIp . ': getting list of databases' . PHP_EOL;
        yield $websocketConnection->send($initResponse);

        yield $listDbs;
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchBuildInfo(Connection $websocketConnection, $instanceIp, $connection)
    {
        $query = new Query('admin.$cmd', ['buildInfo' => 1], null, 0, 1);
        $reply = (yield Awaitable\adapt($connection->send($query)));
        /** @var Reply $reply */
        $response = current(iterator_to_array($reply->getIterator()));
        echo $instanceIp . ': getting build-info' . PHP_EOL;
        yield $websocketConnection->send(Messages\BuildInfo::create($instanceIp, $response));
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchHostInfo(Connection $websocketConnection, $instanceIp, $connection)
    {
        $hostInfoQuery = new Query('admin.$cmd', ['hostInfo' => 1], null, 0, 1);
        $hostInfoReply = (yield Awaitable\adapt($connection->send($hostInfoQuery)));
        /** @var Reply $hostInfoReply */
        $hostInfo = current(iterator_to_array($hostInfoReply->getIterator()));
        echo $instanceIp . ': getting host info' . PHP_EOL;
        yield $websocketConnection->send(Messages\HostInfo::create($instanceIp, $hostInfo));
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchServerStatus(Connection $websocketConnection, $instanceIp, $connection)
    {
        $serverStatusQuery = new Query('admin.$cmd', ['serverStatus' => 1], null, 0, 1);
        $serverStatusReply = (yield Awaitable\adapt($connection->send($serverStatusQuery)));
        /** @var Reply $serverStatusReply */
        $serverStatus = current(iterator_to_array($serverStatusReply->getIterator()));
        echo $instanceIp . ': getting server stats' . PHP_EOL;
        yield $websocketConnection->send(Messages\ServerStatus::create($instanceIp, $serverStatus));
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchTop(Connection $websocketConnection, $instanceIp, $connection)
    {
        $topQuery = new Query('admin.$cmd', ['top' => 1], null, 0, 1);
        $topReply = (yield Awaitable\adapt($connection->send($topQuery)));
        /** @var Reply $topReply */
        $top = current(iterator_to_array($topReply->getIterator()));
        echo $instanceIp . ': getting top' . PHP_EOL;
        yield $websocketConnection->send(Messages\Top::create($instanceIp, $top));
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param string $dbName
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchDbStatus(Connection $websocketConnection, $instanceIp, $dbName, $connection)
    {
        $cacheKey = $instanceIp . $dbName;
        $dbStatsQuery = new Query($dbName . '.$cmd', ['dbStats' => 1], null, 0, 1);
        /** @var Reply $dbStatsReply */
        $dbStatsReply = (yield Awaitable\adapt($connection->send($dbStatsQuery)));
        $dbStats = current(iterator_to_array($dbStatsReply->getIterator()));

        $sum = md5(serialize($dbStats));
        if ($sum !== @$this->cache[$cacheKey]) {
            $this->cache[$cacheKey] = $sum;
            echo $instanceIp . ': sending database stats for [' . $dbName . ']' . PHP_EOL;
            yield $websocketConnection->send(Messages\DbStats::create($instanceIp, $dbStats));
        } else {
            echo $instanceIp . ': no change database stats' . PHP_EOL;
        }
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchLog(Connection $websocketConnection, $instanceIp, $connection)
    {
        $cacheKey = $instanceIp;

        $query = new Query('admin.$cmd', ['getLog' => 'global'], null, 0, 1);
        $reply = (yield Awaitable\adapt($connection->send($query)));
        /** @var Reply $reply */
        $response = current(iterator_to_array($reply->getIterator()));
        $sum = md5(serialize($response));
        if ($sum !== @$this->cache[$cacheKey]) {
            $this->cache[$cacheKey] = $sum;

            echo $instanceIp . ': getting log' . PHP_EOL;
            yield $websocketConnection->send(Messages\Log::create($instanceIp, $response));
        } else {
            echo $instanceIp . ': no change in log' . PHP_EOL;
        }
    }
}
