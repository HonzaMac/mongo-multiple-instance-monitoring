<?php


namespace MongoMonitoring\WebSocket\Messages;


class Top extends Response
{

    /**
     * @param string $hostId
     */
    public static function create($hostId, $top)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'top';
        $serverMessage->data = $top;

        return $serverMessage;
    }

}