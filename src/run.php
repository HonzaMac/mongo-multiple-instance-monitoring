<?php
namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Exception;
use Icicle\Awaitable;
use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Socket;
use Icicle\Stream\MemoryStream;
use Icicle\WebSocket\Server\Server;
use MongoDB;
use MongoMonitoring\Server\ApplicationRequestHandler;

$instancesList = [
    '195.154.78.81:27017', // FRance Les Pros
    '77.93.215.167:27017', // nezname
    '62.109.144.217:27017', // cz ...
    '89.187.142.102:27017',
    '77.93.215.167:27017',
    '88.146.185.3:27017',
    '31.31.78.158:27017', // konec cz
];

$server = new Server(new ApplicationRequestHandler($instancesList, new MemoryStream()));
$server->listen(9900);

Loop\run();


