<?php


namespace MongoMonitoring\Server\Messages;

/**
 * Class Response
 *
 * @property string $hostId
 */
class Response implements IResponse
{

    public function getHostId()
    {
        return $this->hostId;
    }

    public function toJson()
    {
        return json_encode($this);
    }
}