<?php
namespace MongoMonitoring\Server;

use Icicle\Http\Message\BasicResponse;
use Icicle\Http\Message\Request;
use Icicle\Http\Server\RequestHandler;
use Icicle\Socket;

class ApplicationRequestHandler implements RequestHandler
{
    private $application;

    public function __construct($instanceList, &$stream)
    {
        $this->application = new MongoApplication($instanceList);
    }

    public function onRequest(Request $request, Socket\Socket $socket)
    {
        return $this->application;
    }

    public function onError($code, Socket\Socket $socket)
    {
        var_dump($code);
        return new BasicResponse($code);
    }
}