<?php

function dbal_hook_dbal(&$dbinfo)
{
    $store = SimpleSAML_Store::getInstance();

    if (! $store instanceof \SimpleSAML\Modules\DBAL\Store\DBAL ) {
        throw new \SimpleSAML_Error_Exception('OAuth2 module: Only DBAL Store is supported');
    }

    $prefix = $store->getPrefix() . '_kvstore';

    $schema = new \Doctrine\DBAL\Schema\Schema();
    $kvstore = $schema->createTable($prefix);
    $kvstore->addColumn('_type', 'string', array('length' => 30, 'notnull' => true));
    $kvstore->addColumn('_key', 'string', array('length' => 50, 'notnull' => true));
    $kvstore->addColumn('_value', 'text', array('notnull' => true));
    $kvstore->addColumn('_expire', 'datetime', array('notnull' => false));
    $kvstore->setPrimaryKey(array('_key', '_type'));
    $kvstore->addIndex(array('_expire'));

    // Update schema
    $store->createOrUpdateSchema($schema, $prefix);

    $dbinfo['summary'][] = 'Created Key-Value Schema';
}
