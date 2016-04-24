<?php


namespace MongoMonitoring\Server\Messages;

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
    public function toJsonMessage()
    {
        return json_encode($this) . PHP_EOL;
    }
}