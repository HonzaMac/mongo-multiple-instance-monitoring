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


/**
 * Loads file no matter what
 *
 * @param string $path
 * @return null|string
 */
function loadFile($path){
    if (file_exists($path)){
        return file_get_contents($path);
    }
    error_log('File at path ' . $path . ' does not exists.', LOG_INFO);
    return null;
}