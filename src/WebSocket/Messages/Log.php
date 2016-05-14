<?php


namespace MongoMonitoring\WebSocket\Messages;


class Log extends Response
{
    const CNT = 30;

    /**
     * @param string $hostId
     * @param array $logs
     * @return Log
     */
    public static function create($hostId, $logs)
    {
        $serverMessage = (new self);
        $serverMessage->hostId = $hostId;
        $serverMessage->type = 'log';
        $log = $logs['log'];

        $slicedLog = array_slice($log, max(0, count($log) - self::CNT), self::CNT, true);
        $serverMessage->data = $slicedLog;

        return $serverMessage;
    }

}