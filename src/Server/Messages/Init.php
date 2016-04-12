<?php


namespace MongoMonitoring\Server\Messages;

use Ramsey\Uuid\Uuid;

/**
 * @property string $hostId
 * @property string type
 * @property string url
 * @property int totalSize
 * @property array listDBs
 */
class Init implements IResponse
{

    public static function create($inistanceIp, $listDbs)
    {
        $init = (new self);
        $init->hostId = Uuid::uuid4()->toString();
        $init->type = 'init';
        $init->url = $inistanceIp;
        $init->totalSize = $listDbs['totalSize'];
        $init->listDBs = $listDbs;
        return $init;
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