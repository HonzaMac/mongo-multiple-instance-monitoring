<?php


namespace MongoMonitoring\WebSocket\Messages;

/**
 * Class Response
 *
 * @property string $hostId
 * @property string type
 */
abstract class Response implements IResponse
{

    /**
     * @return string
     */
    public function getHostId()
    {
        return $this->hostId;
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this) . PHP_EOL;
    }

}