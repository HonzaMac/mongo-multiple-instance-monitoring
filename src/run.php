#!/usr/bin/env php
<?php

namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Exception;
use Icicle\Awaitable;
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Socket;
use Icicle\Stream\DuplexStream;
use Icicle\Stream\MemorySink;
use Icicle\Stream\MemoryStream;
use Icicle\Stream\Stream;
use Icicle\Stream\Text\TextWriter;
use Icicle\Stream\WritableStream;
use Icicle\WebSocket\Server\Server;
use MongoClient;
use MongoDB;
use MongoMonitoring\Server\ApplicationRequestHandler;
use Ramsey\Uuid\Uuid;

$instancesList = [
    '195.154.78.81:27017', // FRance Les Pros
    '77.93.215.167:27017', // nezname
    '62.109.144.217:27017', // cz ...
    '89.187.142.102:27017',
    '77.93.215.167:27017',
    '88.146.185.3:27017',
    '31.31.78.158:27017', // konec cz
];

/** @var MemoryStream[] $initStreamLists */
$initStreamLists = [];
$loopStreamLists = [];

$generator = function (DuplexStream &$stream, $instanceIp) {
    try {
        $mongoClient = new MongoClient($instanceIp);
        $mongoClient->connect();
        $id = Uuid::uuid4()->toString();
        $listDbs = $mongoClient->listDBs();
        yield $stream->write("TotalSize: " . $listDbs['totalSize'] . PHP_EOL);

        foreach ($listDbs['databases'] as $db) {
            $dbname = $db['name'];
            $mongoDb = new MongoDB($mongoClient, $dbname);
            $db['stats'] = command($mongoDb, 'db.stats()');
            $db['version'] = command($mongoDb, 'db.version()');
            $db['hostInfo'] = command($mongoDb, 'db.hostInfo()');

            $checkSum[$dbname] = null;
            Loop\periodic(1, function () use (&$stream, $mongoDb, $dbname, &$checkSum){
                $stats = command($mongoDb, 'db.stats()');
                if (md5(serialize($stats)) !== $checkSum[$dbname]) {
                    $output = sprintf("DB %s, coll: %s with size %d on storage %d" . PHP_EOL, $stats['db'], $stats['collections'], $stats['dataSize'], $stats['storageSize']);
                    echo $output;
                    yield $stream->write($output . PHP_EOL);
                    $checkSum[$dbname] = md5(serialize($stats));
                } else {
                    $output = '.';
                    echo $output;
                    yield $stream->write($output . PHP_EOL);
                }
            });
        }
        yield;
    } catch (Exception $e) {
        echo 'EXCEPTION: ' . $e->getMessage(). PHP_EOL;
    }
};

//foreach ($instancesList as $ip) {
//    $stream = new MemorySink();
//    $task = new Coroutine\Coroutine($generator($stream, $ip));
//}

$server = new Server(new ApplicationRequestHandler($instancesList, new MemoryStream()));
$server->listen(9900);

//Awaitable\all($coroutines);

Loop\run();

/*
 * Zjistit zakladni informace o pripojene databazi, verzi, stavy na disku,
 * seznam dostupnych kolekci, zapnout loglevel pro slow queries
 *
 * podle seznamu kolekci pak periodicky zjistovat informace o konkretni kolekci
 *
 */



/**
 * @param MongoDB $mongoDb
 * @param string|\MongoCode $mongoCode
 * @return
 * @throws Exception
 */
function command(MongoDB $mongoDb, $mongoCode)
{
    $result = $mongoDb->execute($mongoCode);
    if ($result['ok']) {
        return $result['retval'];
    } else {
        throw new Exception($result['errmsg']);
    }
}

