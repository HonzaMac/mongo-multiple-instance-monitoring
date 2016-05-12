<?php


namespace MongoMonitoring\WebSocket\Messages;


class Log extends Response
{

    /**
     * @param string $hostId
     * @param array $logs
     * @return BuildInfo
     */
    public static function create($hostId, $logs)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'log';
        $serverMessage->data = $logs;

        return $serverMessage;
    }

}