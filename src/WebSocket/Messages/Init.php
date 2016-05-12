<?php


namespace MongoMonitoring\WebSocket\Messages;

/**
 * @property string url
 * @property int totalSize
 * @property array listDBs
 */
class Init extends Response
{

    /**
     * @param string $instanceIp
     * @param string $hostId
     * @param array $listDbs
     * @return Init
     */
    public static function create($instanceIp, $hostId, $listDbs)
    {
        $init = (new self);
        $init->hostId = $hostId;
        $init->type = 'init';
        $init->url = $instanceIp;
        $init->totalSize = $listDbs['totalSize'];
        $init->listDBs = $listDbs;
        return $init;
    }

}