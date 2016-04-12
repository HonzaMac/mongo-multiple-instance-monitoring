<?php
namespace MongoMonitoring\Server\Messages;


/**
 * @property string id
 * @property string type
 * @property int size
 */
interface IResponse
{
    public function getHostId();

    public function toJson();
}