<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string id
 * @property string type
 * @property int size
 */
class Size
{

    public static function create($id, $actualSize)
    {
        $sizeMessage = (new self);
        $sizeMessage->id = $id;
        $sizeMessage->type = 'size';
        $sizeMessage->size = $actualSize;
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