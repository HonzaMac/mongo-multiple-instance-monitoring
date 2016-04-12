<?php


namespace MongoMonitoring\Server\Messages;


/**
 * @property string hostId
 * @property string dbName
 */
class Server implements IResponse
{

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
    
    public function getHostId()
    {
        return $this->hostId;
    }

    public function toJson()
    {
        return json_encode($this);
    }
}