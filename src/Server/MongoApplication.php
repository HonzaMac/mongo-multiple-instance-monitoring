<?php


namespace MongoMonitoring\Server;

use Exception;
use Generator;
use Icicle\Coroutine\Coroutine;
use Icicle\Http\Message\Request;
use Icicle\Http\Message\Response;
use Icicle\Loop;
use Icicle\Socket\Socket;
use Icicle\Stream\DuplexStream;
use Icicle\Stream\MemoryStream;
use Icicle\WebSocket\Application;
use Icicle\WebSocket\Close;
use Icicle\WebSocket\Connection;
use MongoClient;
use MongoDB;
use MongoMonitoring\Server\Messages\Error;
use MongoMonitoring\Server\Messages\Init;
use MongoMonitoring\Server\Messages\Size;

class MongoApplication implements Application
{
    const DELAY = 0.1; // 100ms

    /** @var array */
    private $instanceList;

    /**
     * @var array
     */
    private $ipList;
    /**
     * @var MemoryStream[]
     */
    private $initStreamList;
    /**
     * @var DuplexStream
     */
    private $stream;

    public function __construct($ipList, &$stream)
    {
        $this->ipList = $ipList;
//        $this->initStreamList = $initStreamList;

        $this->stream = $stream;
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param Socket $socket
     * @return Generator|\Icicle\Awaitable\Awaitable|Response|void
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
     * @param \Icicle\WebSocket\Connection $connection
     * @param \Icicle\Http\Message\Response $response
     * @param \Icicle\Http\Message\Request $request
     *
     * @return \Generator|\Icicle\Awaitable\Awaitable|null
     */
    public function onConnection(Connection $connection, Response $response, Request $request)
    {
        foreach ($this->ipList as $instanceIp) {
            $generator = function (Connection $connection, $instanceIp) {
                $mongoClient = new MongoClient($instanceIp);
                $mongoClient->connect();
                $listDbs = $mongoClient->listDBs();
                $databases = $listDbs['databases'];
                $initResponse = Init::create($instanceIp, $listDbs);
                $id = $initResponse->getId();
                $this->instanceList[$id]['client'] = $mongoClient;
                $this->instanceList[$id]['databases'] = $databases;
                yield ($connection->send($initResponse->toJson() . PHP_EOL));

//                foreach ($databases as $db) {
//                    $dbname = $db['name'];
//                    $checkSum[$dbname] = null;
//                    $stats = \MongoMonitoring\command($mongoClient->selectDB($dbname), 'db.stats()');
//                    if (md5(serialize($stats)) !== $checkSum[$dbname]) {
//                        $output = sprintf("DB %s, coll: %s with size %d on storage %d" . PHP_EOL, $stats['db'], $stats['collections'], $stats['dataSize'], $stats['storageSize']);
//                        $checkSum[$dbname] = md5(serialize($stats));
//                        yield ($connection->send($output . PHP_EOL));
//                    }
//
//                }
            };
            yield new Coroutine($generator($connection, $instanceIp));
        }
        $checkSum = [];
        while ($connection->isOpen()) {
            foreach ($this->instanceList as $dbId => $instance) {
                $mongoClient = $instance['client'];
                foreach ($instance['databases'] as $dbKey => $db) {
                    $gen = function (Connection $connection, MongoClient $mongoClient, $dbId, $db, &$checkSum) {
                        try {
                            $stats = \MongoMonitoring\command($mongoClient->selectDB($db['name']), 'db.stats()');
                            $key = $dbId . $db['name'];
                            $sum = md5(serialize($stats));
                            if ($sum !== @$checkSum[$key]) {
                                $checkSum[$key] = $sum;
                                $sizeMessage = Size::create($dbId, $stats['dataSize'], $stats['storageSize']);
                                $output = sprintf("DB %s, coll: %s with size %d on storage %d" . PHP_EOL, $stats['db'], $stats['collections'], $stats['dataSize'], $stats['storageSize']);
                                yield ($connection->send($sizeMessage->toJson() . PHP_EOL));
                            }
                        } catch (Exception $e) {
                            $errorMessage = Error::create($dbId, $e->getMessage(), $e->getCode());
                            yield ($connection->send($errorMessage->toJson() . PHP_EOL));
                            // todo: better error handling
                        }
                    };
                    yield (new Coroutine($gen($connection, $mongoClient, $dbId, $db, $checkSum)))->delay(self::DELAY);
                }
            }
        }

        yield $connection->close(Close::NORMAL, 'Bye Bye.');
    }

    /**
     * @param MongoDB $mongoDb
     * @param string|\MongoCode $mongoCode
     * @return
     * @throws Exception
     */
    static public function command(MongoDB $mongoDb, $mongoCode)
    {
        $result = $mongoDb->execute($mongoCode);
        if ($result['ok']) {
            return $result['retval'];
        } else {
            throw new Exception($result['errmsg']);
        }
    }

}

//  $db['stats'] = \MongoMonitoring\command($mongoDb, 'db.stats()');
//  $db['version'] = \MongoMonitoring\command($mongoDb, 'db.version()');
//  $db['hostInfo'] = \MongoMonitoring\command($mongoDb, 'db.hostInfo()');

