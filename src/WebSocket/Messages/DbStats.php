<?php


namespace MongoMonitoring\WebSocket\Messages;


class DbStats extends Response
{

    /**
     * @param string $hostId
     * @param array $dbStats
     * @return self
     */
    public static function create($hostId, $dbStats)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'dbStats';
        $serverMessage->data = $dbStats;
        return $serverMessage;
    }

}