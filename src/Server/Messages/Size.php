<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string id
 * @property string dbname
 * @property string type
 * @property int size
 * @property int storageSize
 */
class Size
{

    public static function create($id, $dbname, $actualSize, $storageSize)
    {
        $sizeMessage = (new self);
        $sizeMessage->id = $id;
        $sizeMessage->dbname = $dbname;
        $sizeMessage->type = 'size';
        $sizeMessage->size = $actualSize;
        $sizeMessage->storageSize = $storageSize;
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