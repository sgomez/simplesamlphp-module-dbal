<?php

namespace SimpleSAML\Modules\DBAL\Store;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class DBAL extends \SimpleSAML_Store
{
    /**
     * The prefix we should use for our tables.
     *
     * @var string
     */
    private $prefix;

    /**
     * The key-value table prefix.
     *
     * @var string
     */
    private $kvstorePrefix;

    /**
     * Database connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * Initialize the SQL datastore.
     */
    protected function __construct()
    {
        $config = \SimpleSAML_Configuration::getInstance();
        $dbalconfig = \SimpleSAML_Configuration::getConfig('module_dbal.php');

        $this->prefix = $config->getString('store.sql.prefix', 'simpleSAMLphp');
        $this->kvstorePrefix = $this->prefix.'_kvstore';

        $connectionParams = array(
            'driver' => $dbalconfig->getString('store.dbal.driver'),
            'user' => $dbalconfig->getString('store.dbal.user', null),
            'password' => $dbalconfig->getString('store.dbal.password', null),
            'host' => $dbalconfig->getString('store.dbal.host', 'localhost'),
            'dbname' => $dbalconfig->getString('store.dbal.dbname'),
        );
        $this->conn = DriverManager::getConnection($connectionParams);
    }

    /**
     * Create or update a schema.
     *
     * @param Schema $schema
     * @param string $tablePrefix Only tables with this prefix will be updated.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createOrUpdateSchema(Schema $schema, $tablePrefix)
    {
        $manager = $this->conn->getSchemaManager();
        $platform = $this->conn->getDatabasePlatform();

        $origSchema = $manager->createSchema();
        $tables = array();

        foreach ($origSchema->getTables() as $table) {
            if (0 === strpos($table->getName(), $tablePrefix)) {
                $tables[] = $table;
            }
        }

        $migrateSchema = new Schema($tables);
        $queries = $migrateSchema->getMigrateToSql($schema, $platform);

        foreach ($queries as $query) {
            $this->conn->executeQuery($query);
        }
    }

    /**
     * Retrieve a value from the datastore.
     *
     * @param string $type The datatype.
     * @param string $key The key.
     * @return mixed|NULL The value.
     *
     * @throws \SimpleSAML_Error_Error
     */
    public function get($type, $key)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        try {
            $qb = $this->createQueryBuilder()->from($this->kvstorePrefix);
            $query = $qb->select('_value')
                ->where($qb->expr()->eq('_type', ':type'))
                ->andWhere($qb->expr()->eq('_key', ':key'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->isNull('_expire'),
                    $qb->expr()->gt('_expire', ':now')
                ))
                ->setParameter('type', $type, Type::STRING)
                ->setParameter('key', $key, Type::STRING)
                ->setParameter('now', new \DateTime(), Type::DATETIME)
                ->execute();
        } catch (TableNotFoundException $e) {
            throw new \SimpleSAML_Error_Error('KVStore table on the datastore is missing. Did you create the schema? See README.md from dbal module for more information.');
        }

        $result = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) === 0) {
            return null;
        }

        $value = $result[0]['_value'];
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        $value = urldecode($value);
        $value = unserialize($value);

        if (false === $value) {
            return null;
        }

        return $value;
    }

    /**
     * Save a value to the datastore.
     *
     * @param string $type The datatype.
     * @param string $key The key.
     * @param mixed $value The value.
     * @param int|NULL $expire The expiration time (unix timestamp), or NULL if it never expires.
     *
     * @throws \SimpleSAML_Error_Error
     */
    public function set($type, $key, $value, $expire = null)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        if ($expire !== null) {
            $expire = date_timestamp_set(new \DateTime(), $expire);
        }

        $value = serialize($value);
        $value = rawurlencode($value);

        $this->delete($type, $key);

        try {
            $qb = $this->createQueryBuilder();
            $query = $qb->update($this->kvstorePrefix)
                ->set('_value', ':value')
                ->set('_expire', ':expire')
                ->where($qb->expr()->eq('_type', ':type'))
                ->andWhere($qb->expr()->eq('_key', ':key'))
                ->setParameter('type', $type, Type::STRING)
                ->setParameter('key', $key, Type::STRING)
                ->setParameter('value', $value, Type::TEXT)
                ->setParameter('expire', $expire, Type::DATETIME)
            ;
            $rows = $query->execute();
        } catch (TableNotFoundException $e) {
            throw new \SimpleSAML_Error_Error('KVStore table on the datastore is missing. Did you create the schema? See README.md from dbal module for more information.');
        }

        if (0 === $rows) {
            $qb = $this->createQueryBuilder();
            $query = $qb->insert($this->kvstorePrefix)
               ->setValue('_type', ':type')
               ->setValue('_key', ':key')
               ->setValue('_value', ':value')
               ->setValue('_expire', ':expire')
               ->setParameter('type', $type, Type::STRING)
                ->setParameter('key', $key, Type::STRING)
               ->setParameter('value', $value, Type::TEXT)
               ->setParameter('expire', $expire, Type::DATETIME)
            ;
            $query->execute();
        }
    }

    /**
     * Delete a value from the datastore.
     *
     * @param string $type The datatype.
     * @param string $key The key.
     *
     * @throws \SimpleSAML_Error_Error
     */
    public function delete($type, $key)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        try {
            $qb = $this->createQueryBuilder();
            $qb->delete($this->kvstorePrefix)
                ->where($qb->expr()->eq('_type', ':type'))
                ->andWhere($qb->expr()->eq('_key', ':key'))
                ->setParameter('type', $type, Type::STRING)
                ->setParameter('key', $key, Type::STRING)
                ->execute()
            ;
        } catch (TableNotFoundException $e) {
            throw new \SimpleSAML_Error_Error('KVStore table on the datastore is missing. Did you create the schema? See README.md from dbal module for more information.');
        }
    }

    /**
     * Clean the key-value table of expired entries.
     */
    public function cleanKVStore()
    {
        \SimpleSAML_Logger::debug('store.dbal: Cleaning key-value store.');

        try {
            $qb = $this->createQueryBuilder();
            $qb->delete($this->kvstorePrefix)
                ->where($qb->expr()->lt('_expire', ':now'))
                ->setParameter('now', new \DateTime(), Type::DATETIME)
                ->execute()
            ;
        } catch (TableNotFoundException $e) {
            throw new \SimpleSAML_Error_Error('KVStore table on the datastore is missing. Did you create the schema? See README.md from dbal module for more information.');
        }
    }

    /**
     * Create QueryBuilder.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->conn->createQueryBuilder();
    }

    /**
     * Returns the database connection
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
