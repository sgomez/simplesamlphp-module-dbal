# SimpleSAMLphp Composer Doctrine/DBAL module

This package add a new datastore with Doctrine/DBAL library through a SimpleSAMLphp module
installable through [Composer](https://getcomposer.org/). Installation can be as
easy as executing:

```
composer require sgomez/simplesamlphp-module-dbal ~1.0 # for SSP >= 1.14
composer require sgomez/simplesamlphp-module-dbal ~2.0 # for SSP >= 2.0|master
```


## Configuring

You need to specify the next _store.type_ on your config file:

```[php]
    'store.type'                    => 'SimpleSAML\Modules\DBAL\Store\DBAL',
```

And copy the template config file from `modules/dbal/config-templates/module_dbal.php` to your config base directory.
You must edit it with your database connection configuration.

This module supports the same engines than _Doctrine/DBAL_. See
[Doctrine DBAL configuration](http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html)
for the syntax.

```[php]
$config = array (
    'store.dbal.dbname'             => 'simplesamlphp',
    'store.dbal.user'               => 'user',
    'store.dbal.password'           => 'password',
    'store.dbal.host'               => 'localhost',
    'store.dbal.driver'             => 'pdo_mysql',
);
```

If you want to clean old keys automatically, remember to enable and configure the _cron_ module.

## Creating the Schema

The schema is not created every time than Store is called (like SQL store). You need to created it manually.
You need to run this every time you install or update a module than use DBAL Store:
 
 ```[bash]
 bash$ vendor/bin/dbalschema
 ```

## Creating new schemas

If you want to create your own schema to your module, you need to create a `hook_dbal.php` file on your _hooks_ directory.
This file will run every time than _dbalschema_ is launched.

This is a template:

```[php]
<?php

function modulename_hook_dbal(&$dbinfo)
{
    $store = SimpleSAML_Store::getInstance();
    
    if (! $store instanceof \SimpleSAML\Modules\DBAL\Store\DBAL ) {
        throw new \SimpleSAML_Error_Exception('OAuth2 module: Only DBAL Store is supported');
    }
    
    $schema = new \Doctrine\DBAL\Schema\Schema();
    
    $fooTable = $store->getPrefix().'_foo';
    $foo = $schema->createTable($fooTable);
    $foo->addColumn('id', 'string', [ 'length' => 255 ]);
    $foo->addColumn('name', 'string', [ 'length' => 255 ]);
    $foo->setPrimaryKey(['id']);
    
    $barTable = $store->getPrefix().'_bar';
    $bar = $schema->createTable($barTable);
    $bar->addColumn('id', 'string', [ 'length' => 255 ]);
    $bar->addColumn('expires_at', 'datetime');
    $bar->addColumn('foo_id', 'string', [ 'length' => 255 ]);
    $bar->setPrimaryKey(['id']);
    $bar->addForeignKeyConstraint($foo, ['foo_id'], ['id'], ['onDelete' => 'CASCADE']);
    
    $store->createOrUpdateSchema($schema, $store->getPrefix().'_modulename');
    
    $dbinfo['summary'][] = 'Created ModuleName Schema';
}

```

_Doctrine DBAL_ is able to update your schema in almost any case without drop it. To know
all types and options see [Doctrine DBAL Documentation](http://doctrine-dbal.readthedocs.org/en/latest/).
