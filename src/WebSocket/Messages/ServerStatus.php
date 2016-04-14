<?php


namespace MongoMonitoring\WebSocket\Messages;


class ServerStatus extends Response
{

    /**
     * @param string $hostId
     * @param array $serverStatus
     * @return self
     */
    public static function create($hostId, $serverStatus)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'serverStatus';
        $serverMessage->data = $serverStatus;
        return $serverMessage;
    }

}