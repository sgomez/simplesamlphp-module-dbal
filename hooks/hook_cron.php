<?php

function dbal_hook_cron(&$croninfo)
{
    $store = SimpleSAML_Store::getInstance();

    if (! $store instanceof \SimpleSAML\Modules\DBAL\Store\DBAL ) {
        throw new \SimpleSAML_Error_Exception('OAuth2 module: Only DBAL Store is supported');
    }

    $store->cleanKVStore();

    $dbinfo['summary'][] = 'Cleaned Key-Value Store';
}