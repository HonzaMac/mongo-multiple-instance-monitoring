<?php


namespace MongoMonitoring\Server\Messages;

/**
 * @property string type
 * @property string url
 * @property int totalSize
 * @property array listDBs
 */
class Init extends Response
{

    /**
     * @param string $inistanceIp
     * @param array $listDbs
     * @return Init
     */
    public static function create($inistanceIp, $listDbs)
    {
        $init = (new self);
        $init->hostId = md5($inistanceIp);
        $init->type = 'init';
        $init->url = $inistanceIp;
        $init->totalSize = $listDbs['totalSize'];
        $init->listDBs = $listDbs;
        return $init;
    }

}