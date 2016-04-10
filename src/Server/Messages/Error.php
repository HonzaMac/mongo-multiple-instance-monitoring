<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string id
 * @property string type
 * @property int size
 */
class Error
{

    public static function create($id, $message, $code = 0)
    {
        $sizeMessage = (new self);
        $sizeMessage->id = $id;
        $sizeMessage->type = 'error';
        $sizeMessage->message = $message;
        $sizeMessage->code = $code;
        return $sizeMessage;
    }

    public function getId()
    {
        return $this->id;
    }

    public function toJson()
    {
        return json_encode($this);
    }

}