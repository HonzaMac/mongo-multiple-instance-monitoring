<?php

namespace MongoMonitoring\Server\Messages;

/**
 * @property string dbname
 * @property int size
 * @property int storageSize
 */
class Size extends Response
{

    /**
     * @param string $hostId
     * @param string $dbname
     * @param int $actualSize
     * @param int $storageSize
     * @return Size
     */
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