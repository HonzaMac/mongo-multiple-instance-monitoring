<?php


namespace MongoMonitoring\Server\Messages;


use Ramsey\Uuid\Uuid;

/**
 * @property string id
 * @property string type
 * @property string url
 * @property int totalSize
 * @property array listDBs
 */
class Init
{

    public static function create($inistanceIp, $listDbs)
    {
        $init = (new self);
        $init->id = Uuid::uuid4()->toString();
        $init->type = 'init';
        $init->url = $inistanceIp;
        $init->totalSize = $listDbs['totalSize'];
        $init->listDBs = $listDbs;
        return $init;
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