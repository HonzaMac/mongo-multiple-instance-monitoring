<?php


namespace MongoMonitoring\WebSocket\Messages;


class BuildInfo extends Response
{

    /**
     * @param string $hostId
     * @param array $version
     * @return BuildInfo
     */
    public static function create($hostId, $version)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'buildInfo';
        $serverMessage->data = $version;

        return $serverMessage;
    }

}