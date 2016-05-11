<?php
namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Awaitable;
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\ReactAdapter\ReactLoop;
use Icicle\Socket;
use Icicle\Stream\MemoryStream;
use Icicle\WebSocket\Server\Server;
use Jmikola\React\MongoDB\ConnectionFactory;
use MongoDB;
use MongoMonitoring\Server\ApplicationRequestHandler;
use MongoMonitoring\WebSocket\Handler;
use Nette\Neon\Neon;
use React\EventLoop\Factory;

ini_set('xdebug.max_nesting_level', PHP_INT_MAX);


$defaultPort = 9900;
$configPath = 'config.neon';
$fileContent = loadFile($configPath);
if (null === $fileContent) {
    $fileContent = loadFile('../'. $configPath);
}
$config = Neon::decode($fileContent);
$port = isset($config['server']['port'])?$config['server']['port']: $defaultPort;

$icicleLoop = new ReactLoop();
$server = new Server(new Handler($icicleLoop, $config['hosts']));
$server->listen($port);
echo 'Websocket server is listenning on port ' . $port . PHP_EOL;

$icicleLoop->run();


