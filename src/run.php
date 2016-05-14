<?php
namespace MongoMonitoring;

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Loop;
use Icicle\ReactAdapter\ReactLoop;
use Icicle\WebSocket\Server\Server;
use MongoMonitoring\WebSocket\Handler;
use Nette\Neon\Neon;

ini_set('xdebug.max_nesting_level', PHP_INT_MAX);
ini_set('mongo.long_as_object', 1);


$configPath = 'config.neon';
$fileContent = loadFile($configPath);
if (null === $fileContent) {
    $fileContent = loadFile('../' . $configPath);
}
$config = Neon::decode($fileContent);
$port = isset($config['server']['port']) ? $config['server']['port'] : 9900;
$host = isset($config['server']['host']) ? $config['server']['host'] : '0.0.0.0';
$explodedHosts = explode(',', getenv('HOSTS'));
$envHosts = (isset($explodedHosts[0]) && $explodedHosts[0] === '') ? [] : $explodedHosts;
$config['hosts'] = array_merge(
    $config['hosts'] === null ? [] : $config['hosts'],
    $envHosts);

$loop = Loop\loop();
$server = new Server(new Handler(new ReactLoop($loop), $config['hosts']));
$server->listen($port, $host);
echo 'Websocket server is listening on port ' . $port . PHP_EOL;

$loop->run();


