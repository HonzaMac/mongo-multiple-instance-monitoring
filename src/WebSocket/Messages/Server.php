<?php


namespace MongoMonitoring\WebSocket\Messages;


/**
 * @property string dbName
 */
class Server extends Response
{

    /**
     * @param string $hostId
     * @param string $dbName
     * @param array $serverStatus
     * @return Server
     */
    public static function create($hostId, $dbName, $serverStatus)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->dbName = $dbName;
        $serverMessage->type = 'server';

        $serverMessage->network = $serverStatus['network'];
        $serverMessage->opCounters = $serverStatus['opcounters'];
        return $serverMessage;
    }

}