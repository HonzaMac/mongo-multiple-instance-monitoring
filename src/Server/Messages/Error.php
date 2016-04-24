<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string type
 * @property int size
 */
class Error extends Response
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

}