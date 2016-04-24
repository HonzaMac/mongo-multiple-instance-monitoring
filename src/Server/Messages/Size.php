<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string dbname
 * @property string type
 * @property int size
 * @property int storageSize
 */
class Size extends Response
{

    public static function create($hostId, $dbname, $actualSize, $storageSize)
    {
        $sizeMessage = (new self);
        $sizeMessage->hostId = $hostId;
        $sizeMessage->dbname = $dbname;
        $sizeMessage->type = 'size';
        $sizeMessage->size = $actualSize;
        $sizeMessage->storageSize = $storageSize;
        return $sizeMessage;
    }

}