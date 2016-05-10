<?php
namespace MongoMonitoring\HttpServer;


use Icicle\Http\Message\BasicResponse;
use Icicle\Http\Message\Request;
use Icicle\Http\Server\RequestHandler;
use Icicle\Socket\Socket;
use Icicle\Stream\MemoryStream;

class HttpRequestHandler implements RequestHandler
{
    /**
     * @param \Icicle\Http\Message\Request $request
     * @param \Icicle\Socket\Socket $socket
     *
     * @return \Generator|\Icicle\Awaitable\Awaitable|\Icicle\Http\Message\Response
     */
    public function onRequest(Request $request, Socket $socket)
    {
        $memory = new MemoryStream();
        $memory->write('Out');
        $memory->end('End');
        return new BasicResponse(200, [], $memory);
    }

    /**
     * @param int $code
     * @param \Icicle\Socket\Socket $socket
     *
     * @return \Generator|\Icicle\Awaitable\Awaitable|\Icicle\Http\Message\Response
     */
    public function onError($code, Socket $socket)
    {
//        throw new BasicResponse(500, []);
    }
}