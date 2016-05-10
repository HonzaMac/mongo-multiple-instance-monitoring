<?php
namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine\Coroutine;
use Icicle\Loop;
use Icicle\Stream\MemoryStream;
use Icicle\WebSocket\Server\Server as WebSocketServer;
use Icicle\Http\Server\Server as HttpServer;
use MongoMonitoring\HttpServer\HttpRequestHandler;
use MongoMonitoring\WebsocketServer\Messages\Init;
use MongoMonitoring\WebsocketServer\WebSocketRequestHandler;
use Nette\Neon\Neon;

$defaultPort = 9900;
$configPath = 'config.neon';
$fileContent = loadFile($configPath);
if (null === $fileContent) {
    $fileContent = loadFile('../'. $configPath);
}
$config = Neon::decode($fileContent);
$webSocketPort = isset($config['server']['port'])?$config['server']['port']: $defaultPort;
$webServerPort = 8080;

$server = new WebSocketServer(new WebSocketRequestHandler($config['hosts'], new MemoryStream()));
$server->listen($webSocketPort);

$cache = [];
$httpServer = new HttpServer(new HttpRequestHandler($cache));
$httpServer->listen($webServerPort);

$generator2 = function ($instanceIp) {
    $mongoClient = new \MongoClient($instanceIp);
    $mongoClient->connect();
    $listDbs = $mongoClient->listDBs();
    $databases = $listDbs['databases'];
    $initResponse = Init::create($instanceIp, $listDbs);
    $id = $initResponse->getHostId();
    $this->instanceList[$id]['client'] = $mongoClient;
    $this->instanceList[$id]['databases'] = $databases;
    yield $initResponse;
};

$generator = function ($intanceIp) use ($generator2){
    echo $intanceIp;
    yield new Coroutine($generator2($intanceIp));
};


foreach ($config['hosts'] as $instanceIp) {
    Loop\periodic(3, function ($timer) use ($instanceIp){
        echo $instanceIp;

    }, $config['hosts']);
}


//foreach ($this->ipList as $instanceIp) {
//    yield new Coroutine($this->fetchInit($connection, $instanceIp));
//}



Loop\run();


