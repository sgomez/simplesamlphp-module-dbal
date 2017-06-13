<?php

$config = array (
    /*
     * The Doctrine/DBAL datastore should connect to.
     *
     * See http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html
     * for the various syntaxes.
     */

    'store.dbal.url'                => 'mysql://user:password@localhost:3306/simplesamlphp?charset=utf8mb4&serverVersion=5.7',
    // 'store.dbal.url'                => 'sqlite:///simplesamlphp.sqlite',


    // This way is deprecated

    //    'store.dbal.dbname'             => 'simplesamlphp',
    //    'store.dbal.user'               => 'user',
    //    'store.dbal.password'           => 'password',
    //    'store.dbal.host'               => 'localhost',
    //    'store.dbal.driver'             => 'pdo_mysql',
);
