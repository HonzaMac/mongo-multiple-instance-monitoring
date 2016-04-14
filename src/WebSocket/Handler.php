<?php
namespace MongoMonitoring\WebSocket;

use Icicle\Http\Message\BasicResponse;
use Icicle\Http\Message\Request;
use Icicle\Http\Server\RequestHandler;
use Icicle\Socket;
use Jmikola\React\MongoDB\ConnectionFactory;

class Handler implements RequestHandler
{
    private $application;

    public function __construct($loop, $instanceList)
    {
        $this->application = new MongoApplication(new ConnectionFactory($loop), $instanceList);
    }

    public function onRequest(Request $request, Socket\Socket $socket)
    {
        return $this->application;
    }

    public function onError($code, Socket\Socket $socket)
    {
        return new BasicResponse($code);
    }
}