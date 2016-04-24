<?php
namespace MongoMonitoring\Server\Messages;


interface IResponse
{
    /**
     * @return string
     */
    public function getHostId();

    /**
     * @return string
     */
    public function __toString();
}