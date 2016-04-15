<?php
namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Awaitable;
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Socket;
use Icicle\Stream\MemoryStream;
use Icicle\WebSocket\Server\Server;
use MongoDB;
use MongoMonitoring\Server\ApplicationRequestHandler;
use Nette\Neon\Neon;

$defaultPort = 9900;
$configPath = 'config.neon';
$fileContent = loadFile($configPath);
if (null === $fileContent) {
    $fileContent = loadFile('../'. $configPath);
}
$config = Neon::decode($fileContent);
$port = isset($config['server']['port'])?$config['server']['port']: $defaultPort;

$server = new Server(new ApplicationRequestHandler($config['hosts'], new MemoryStream()));
$server->listen($port);

Loop\run();


