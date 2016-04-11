<?php

/**
 * @param MongoDB $mongoDb
 * @param string|\MongoCode $mongoCode
 * @return
 * @throws Exception
 */
function command(MongoDB $mongoDb, $mongoCode)
{
    $result = $mongoDb->execute($mongoCode);
    if ($result['ok']) {
        return $result['retval'];
    } else {
        throw new Exception($result['errmsg']);
    }
}

