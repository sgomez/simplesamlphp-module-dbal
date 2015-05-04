# SimpleSAMLphp Composer Doctrine/DBAL module

This package add a new datastore with Doctrine/DBAL library through a SimpleSAMLphp module
installable through [Composer](https://getcomposer.org/). Installation can be as
easy as executing:

```
composer.phar require sgomez/simplesamlphp-module-dbal ~1.0@dev
```

## Configuring

You need to specify the next _store.type_ on your config file:

```[php]
    'store.type'                    => 'SimpleSAML\Modules\DBAL\Store\DBAL',
```

## Creating the Schema

The schema is not created every time than Store is called (like SQL store). You need to created it manually.
You need to run this every time you install or update a module than use DBAL Store:
 
 ```[bash]
 bash$ vendor/bin/dbalschema
 ```
 