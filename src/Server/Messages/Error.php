<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string type
 * @property int size
 */
class Error extends Response
{

    /**
     * @param string $hostId
     * @param string $message
     * @param int $code
     * @return Error
     */
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