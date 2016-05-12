<?php


namespace MongoMonitoring\WebSocket\Messages;


class HostInfo extends Response
{

    /**
     * @param string $hostId
     * @param array $hostInfo
     * @return self
     */
    public static function create($hostId, $hostInfo)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'hostInfo';
        $serverMessage->data = $hostInfo;

        return $serverMessage;
    }

}