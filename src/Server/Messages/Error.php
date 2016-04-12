<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string $hostId
 * @property string type
 * @property int size
 */
class Error implements IResponse
{

    public static function create($hostId, $message, $code = 0)
    {
        $sizeMessage = (new self);
        $sizeMessage->hostId = $hostId;
        $sizeMessage->type = 'error';
        $sizeMessage->message = $message;
        $sizeMessage->code = $code;
        return $sizeMessage;
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