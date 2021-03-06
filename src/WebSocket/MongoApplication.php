<?php


namespace MongoMonitoring\WebSocket;

use Exception;
use Generator;
use Icicle\Awaitable;
use Icicle\Coroutine;
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
use MongoMonitoring\WebSocket\Messages;

class MongoApplication implements Application
{
    const PERIODIC_CHECK_IN_SECONDS = 10;
    const SERVER_CONNECTION_TIMEOUT_IN_SECONDS = 2;
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
        $cache = [];
        $coroutines = [];
        foreach ($this->ipList as $instanceIp) {
            $coroutines[] = new Coroutine\Coroutine($this->fetch($instanceIp, $cache));
        }
        $connectedInstances = (yield Awaitable\all($coroutines));
        $connectedInstances = array_filter($connectedInstances);

        foreach ($connectedInstances as list($connection, $instanceIp, $databases, $buildInfo, $hostInfo, $serverStatus, $top)) {
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting list of databases');
            yield $websocketConnection->send(Messages\Init::create($instanceIp, $instanceIp, $databases));
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting build-info');
            yield $websocketConnection->send(Messages\BuildInfo::create($instanceIp, $buildInfo));
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting host info');
            yield $websocketConnection->send(Messages\HostInfo::create($instanceIp, $hostInfo));
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting server stats');
            yield $websocketConnection->send(Messages\ServerStatus::create($instanceIp, $serverStatus));
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting top');
            yield $websocketConnection->send(Messages\Top::create($instanceIp, $top));
        }


        while ($websocketConnection->isOpen()) {
            foreach ($connectedInstances as list($connection, $instanceIp, $databases)) {
                yield $this->databasesCoroutines($websocketConnection, $databases, $instanceIp, $connection, $cache);
                yield (new Coroutine\Coroutine($this->fetchLog($websocketConnection, $instanceIp, $connection, $cache)))->wait();
                yield (new Coroutine\Coroutine($this->fetchServerStatus($instanceIp, $connection, $cache)))->wait();
            }
            yield Coroutine\sleep(self::PERIODIC_CHECK_IN_SECONDS);

        }
        foreach ($connectedInstances as list($connection, $instanceIp, $databases)) {
            yield $connection->close();
        }

    }


    /**
     * @param string $instanceIp
     * @param $cache
     * @return Generator
     */
    public function fetch($instanceIp, &$cache)
    {
        list($host, $port) = explode(':', $instanceIp);
        $connection = (yield $this->connectToHost($instanceIp, $host, $port));
        if ($connection) {
            $listDbs = (yield $this->fetchListDatabase($connection));
            $buildInfo = (yield $this->fetchBuildInfo($connection));
            $hostInfo = (yield $this->fetchHostInfo($connection));
            $serverStatus = (yield $this->fetchServerStatus($instanceIp, $connection, $cache));
            $top = (yield $this->fetchTop($connection));

            yield [$connection, $instanceIp, $listDbs, $buildInfo, $hostInfo, $serverStatus, $top];
        } else {
            yield null;
        }
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
        $lastAwaitable = $connectionThenable = Awaitable\adapt($this->connectionFactory->create($host, $port, ['connectTimeoutMS' => 500, 'socketTimeoutMS' => 500]));
        $lastAwaitable = $lastAwaitable->timeout(self::SERVER_CONNECTION_TIMEOUT_IN_SECONDS, function () use ($connectionThenable, $instanceIp) {
            echo $instanceIp . ' timed out after ' . self::SERVER_CONNECTION_TIMEOUT_IN_SECONDS . ' seconds' . PHP_EOL;
            $connectionThenable->cancel();
            return null;
        });
        $lastAwaitable = $lastAwaitable->then(function () use ($instanceIp, $connectionThenable) {
            echo $instanceIp . ' connected' . PHP_EOL;
            return $connectionThenable;
        }, function (Exception $exception) use ($instanceIp) {
            echo $instanceIp . ' failed to connect with reason: ' . $exception->getMessage() . PHP_EOL;
            return null;
        });

        yield $lastAwaitable;
    }

    /**
     * @param MongoConnection $connection
     * @return array
     */
    private function fetchListDatabase($connection)
    {
        $listDatabasesQuery = new Query('admin.$cmd', ['listDatabases' => 1], null, 0, 1);
        /** @var Reply $reply */
        $reply = (yield Awaitable\adapt($connection->send($listDatabasesQuery)));
        $listDbs = current(iterator_to_array($reply));
        yield $listDbs;
    }

    /**
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchBuildInfo($connection)
    {
        $query = new Query('admin.$cmd', ['buildInfo' => 1], null, 0, 1);
        $reply = (yield Awaitable\adapt($connection->send($query)));
        /** @var Reply $reply */
        $buildInfo = current(iterator_to_array($reply));
        yield $buildInfo;
    }

    /**
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchHostInfo($connection)
    {
        $hostInfoQuery = new Query('admin.$cmd', ['hostInfo' => 1], null, 0, 1);
        $hostInfoReply = (yield Awaitable\adapt($connection->send($hostInfoQuery)));
        /** @var Reply $hostInfoReply */
        $hostInfo = current(iterator_to_array($hostInfoReply));
        yield $hostInfo;
    }

    /**
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @param array $cache
     * @return Generator
     */
    private function fetchServerStatus($instanceIp, $connection, &$cache)
    {
        $cacheKey = $instanceIp;

        $serverStatusQuery = new Query('admin.$cmd', ['serverStatus' => 1], null, 0, 1);
        $serverStatusReply = (yield Awaitable\adapt($connection->send($serverStatusQuery)));
        /** @var Reply $serverStatusReply */
        $serverStatusResponse = current(iterator_to_array($serverStatusReply));
        $sum = md5(serialize($serverStatusResponse));
        if ($sum !== @$cache[$cacheKey]) {
            $cache[$cacheKey] = $sum;
            yield $serverStatusResponse;
        }
    }

    /**
     * @param MongoConnection $connection
     * @return Generator
     */
    private function fetchTop($connection)
    {
        $topQuery = new Query('admin.$cmd', ['top' => 1], null, 0, 1);
        $topReply = (yield Awaitable\adapt($connection->send($topQuery)));
        /** @var Reply $topReply */
        $top = current(iterator_to_array($topReply));
        yield $top;
    }

    /**
     * @param string $remoteAddress
     * @param int $remotePort
     * @param string $instanceIp
     * @param string $message
     */
    private function log($remoteAddress, $remotePort, $instanceIp, $message)
    {
        $clientPrefix = $remoteAddress . ':' . $remotePort;
        echo $clientPrefix . ' => ' . $instanceIp . ': ' . $message . PHP_EOL;
    }

    /**
     * @param Connection $websocketConnection
     * @param array $databases
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @param array $cache
     * @return Generator
     */
    private function databasesCoroutines(Connection $websocketConnection, $databases, $instanceIp, $connection, &$cache)
    {
        foreach ($databases['databases'] as $db) {
            yield (new Coroutine\Coroutine($this->fetchDbStatus($websocketConnection, $instanceIp, $db['name'], $connection, $cache)));
        }
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param string $dbName
     * @param MongoConnection $connection
     * @param $cache
     * @return Generator
     */
    private function fetchDbStatus(Connection $websocketConnection, $instanceIp, $dbName, $connection, &$cache)
    {
        $cacheKey = $instanceIp . $dbName;
        $dbStatsQuery = new Query($dbName . '.$cmd', ['dbStats' => 1], null, 0, 1);
        /** @var Reply $dbStatsReply */
        $dbStatsReply = (yield Awaitable\adapt($connection->send($dbStatsQuery)));
        $dbStats = current(iterator_to_array($dbStatsReply));

        $sum = md5(serialize($dbStats));
        if ($sum !== @$cache[$cacheKey]) {
            $cache[$cacheKey] = $sum;
            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'sending database stats for [' . $dbName . ']');
            yield $websocketConnection->send(Messages\DbStats::create($instanceIp, $dbStats));
        }
    }

    /**
     * @param Connection $websocketConnection
     * @param string $instanceIp
     * @param MongoConnection $connection
     * @param array $cache
     * @return Generator
     */
    private function fetchLog(Connection $websocketConnection, $instanceIp, $connection, &$cache)
    {
        $cacheKey = $instanceIp;

        $query = new Query('admin.$cmd', ['getLog' => 'global'], null, 0, 1);
        $reply = (yield Awaitable\adapt($connection->send($query)));
        /** @var Reply $reply */
        $response = current(iterator_to_array($reply));
        $sum = md5(serialize($response));
        if ($sum !== @$cache[$cacheKey]) {
            $cache[$cacheKey] = $sum;

            $this->log($websocketConnection->getRemoteAddress(), $websocketConnection->getRemotePort(), $instanceIp, 'getting log');
            yield $websocketConnection->send(Messages\Log::create($instanceIp, $response));
        }
    }
}
