<?php


namespace MongoMonitoring\Server\Messages;

/**
 * Class Response
 *
 * @property string $hostId
 */
class Response implements IResponse
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
    public function toJson()
    {
        return json_encode($this);
    }
}