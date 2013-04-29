<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 */
namespace Orno\Db;

/**
 * Query
 *
 * Object to handle calls to database driver objects, provides an interface to write
 * ANSI/ISO SQL without object oriented query building with support for transactions.
 */
class Query
{
    /**
     * Database connection driver
     *
     * @var \Orno\Db\Driver\DriverInterface
     */
    protected $driver;

    /**
     * Configuration array
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param \Orno\Db\Driver\DriverInterface
     * @param array $config
     */
    public function __construct(Driver\DriverInterface $driver, array $config = [])
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    /**
     * Get Driver
     *
     * Return an instance of the driver
     *
     * @return \Orno\Db\Driver\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set Driver
     *
     * Provide an implementation of the driver interface
     *
     * @param  \Orno\Db\Driver\DriverInterface $driver
     * @return \Orno\Db\Query
     */
    public function setDriver(Driver\DriverInterface $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Prepare
     *
     * Prepare a query with the database driver
     *
     * @param  string $query
     * @return \Orno\Db\Query
     */
    public function prepare($query)
    {
        if (! empty($this->config)) {
            $this->getDriver()->connect($config);
        }

        $this->getDriver()->prepareQuery($query);
        return $this;
    }

    /**
     * Bind
     *
     * Bind a value to a placeholder in the most recent prepared query
     *
     * @param  mixed $placeholder
     * @param  mixed $value
     * @param  int   $type
     * @return \Orno\Db\Query
     */
    public function bind($placeholder, $value, $type = null)
    {
        if (is_null($type)) {
            $this->getDriver()->bind($placeholder, $value);
        } else {
            $this->getDriver()->bind($placeholder, $value, $type);
        }

        return $this;
    }

    /**
     * Execute
     *
     * Execute the most recent prepared query
     *
     * @return \Orno\Db\Query
     */
    public function execute()
    {
        $this->getDriver()->execute();
        return $this;
    }

    /**
     * Transaction
     *
     * Start a transaction with the database driver
     *
     * @return \Orno\Db\Query
     */
    public function transaction()
    {
        if (! empty($this->config)) {
            $this->getDriver()->connect($config);
        }

        $this->getDriver()->transaction();
        return $this;
    }

    /**
     * Commit
     *
     * Commit the transaction
     *
     * @return \Orno\Db\Query
     */
    public function commit()
    {
        $this->getDriver()->commit();
        return $this;
    }

    /**
     * Rollback
     *
     * Rollback the transaction
     *
     * @return \Orno\Db\Query
     */
    public function rollback()
    {
        $this->getDriver()->rollback();
        return $this;
    }

    /**
     * Fetch
     *
     * Fetch an associative array of the next row in the result set
     *
     * @return array
     */
    public function fetch()
    {
        return $this->getDriver()->fetch();
    }

    /**
     * Fetch Object
     *
     * Fetch stdClass object of the next row in the result set
     *
     * @return object
     */
    public function fetchObject()
    {
        return $this->getDriver()->fetchObject();
    }

    /**
     * Fetch
     *
     * Fetch a multi-dimensional array of all rows in the result set
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->getDriver()->fetchAll();
    }
}
