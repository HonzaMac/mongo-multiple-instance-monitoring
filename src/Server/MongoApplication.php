<?php


namespace MongoMonitoring\Server;

use Exception;
use Generator;
use Icicle\Awaitable\Awaitable;
use Icicle\Coroutine\Coroutine;
use Icicle\Http\Message\Request;
use Icicle\Http\Message\Response;
use Icicle\Loop;
use Icicle\Socket\Socket;
use Icicle\WebSocket\Application;
use Icicle\WebSocket\Close;
use Icicle\WebSocket\Connection;
use MongoClient;
use MongoDB;
use MongoMonitoring\Server\Messages\Error;
use MongoMonitoring\Server\Messages\Init;
use MongoMonitoring\Server\Messages\Server;
use MongoMonitoring\Server\Messages\Size;

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

    public function __construct($ipList)
    {
        $this->ipList = $ipList;
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param Socket $socket
     * @return Generator|Awaitable|Response|void
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
     * @param Connection $connection
     * @param Response $response
     * @param Request $request
     *
     * @return Generator|Awaitable|null
     */
    public function onConnection(Connection $connection, Response $response, Request $request)
    {
        foreach ($this->ipList as $instanceIp) {
            yield new Coroutine($this->fetchInit($connection, $instanceIp));
        }
        $checkSum = $checkSumServerStatus = [];
        while ($connection->isOpen()) {


            foreach ($this->instanceList as $dbId => $instance) {
                foreach ($instance['databases'] as $dbKey => $db) {
                    yield (new Coroutine($this->fetchServerStats($connection, $instance['client'], $dbId, $db, $checkSumServerStatus)))->delay(self::DELAY_SERVER_STATUS);
                    yield (new Coroutine($this->fetchStats($connection, $instance['client'], $dbId, $db, $checkSum)))->delay(self::DELAY);
                }
            }
        }

        yield $connection->close(Close::NORMAL, 'Bye Bye.');
    }

    /**
     * @param Connection $connection
     * @param MongoClient $mongoClient
     * @param $dbId
     * @param $db
     * @param $checkSum
     * @return Generator
     */
    protected function fetchStats(Connection $connection, MongoClient $mongoClient, $dbId, $db, &$checkSum)
    {
        try {
            $stats = command($mongoClient->selectDB($db['name']), 'db.stats()');
            $key = $dbId . $db['name'];
            $sum = md5(serialize($stats));
            if ($sum !== @$checkSum[$key]) {
                $checkSum[$key] = $sum;
                $sizeMessage = Size::create($dbId, $db['name'], $stats['dataSize'], $stats['storageSize']);
                $output = sprintf("DB %s, coll: %s with size %d on storage %d" . PHP_EOL, $stats['db'], $stats['collections'], $stats['dataSize'], $stats['storageSize']);
                yield ($connection->send($sizeMessage));
            }
        } catch (Exception $e) {
            $errorMessage = Error::create($dbId, $e->getMessage(), $e->getCode());
            yield ($connection->send($errorMessage));
        }
    }

    /**
     * @param Connection $connection
     * @param string $instanceIp
     * @return Generator
     */
    public function fetchInit(Connection $connection, $instanceIp)
    {
        $mongoClient = new MongoClient($instanceIp);
        $mongoClient->connect();
        $listDbs = $mongoClient->listDBs();
        $databases = $listDbs['databases'];
        $initResponse = Init::create($instanceIp, $listDbs);
        $id = $initResponse->getHostId();
        $this->instanceList[$id]['client'] = $mongoClient;
        $this->instanceList[$id]['databases'] = $databases;
        yield ($connection->send($initResponse));
    }

    /**
     * @param Connection $connection
     * @param MongoClient $client
     * @param string $dbId
     * @param $db
     * @param array $checkSum
     * @return Generator
     */
    private function fetchServerStats(Connection $connection, MongoClient $client, $dbId, $db, $checkSum)
    {
        try {
            $serverStatus = command($client->selectDB($db['name']), 'db.serverStatus()');
            $key = $dbId . $db['name'];
            $sum = md5(serialize($serverStatus));
            if ($sum !== @$checkSum[$key]) {
                $checkSum[$key] = $sum;
                $serverMessage = Server::create($dbId, $db['name'], $serverStatus);
                yield ($connection->send($serverMessage));
            }

        } catch (Exception $e) {
            $errorMessage = Error::create($dbId, $e->getMessage(), $e->getCode());
            yield ($connection->send($errorMessage));
        }
    }

}

//  $db['stats'] = \MongoMonitoring\command($mongoDb, 'db.stats()');
//  $db['version'] = \MongoMonitoring\command($mongoDb, 'db.version()');
//  $db['hostInfo'] = \MongoMonitoring\command($mongoDb, 'db.hostInfo()');

