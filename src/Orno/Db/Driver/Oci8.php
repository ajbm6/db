<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see LICENSE file)
 */
namespace Orno\Db\Driver;

use Orno\Db\Exception;

/**
 * Oci8 Driver
 *
 * A database abstraction for PHP OCI8 functionality.
 *
 * Note: It is recommended, for performance improvements, to set the oci8.default_prefetch
 * option in your php.ini. This will significantly improve network performance as it
 * will reduce the number of round trips on the network by buffering rows into the SQL*Net
 * transport cache.
 */
class Oci8 implements DriverInterface
{
    /**
     * Type constants
     */
    const PARAM_STR  = 1;
    const PARAM_INT  = 2;
    const PARAM_BOOL = 3;
    const PARAM_BIN  = 4;
    const PARAM_FLT  = 5;

    /**
     * The Oci8 Connection Resource
     *
     * @var resource
     */
    protected $connection;

    /**
     * The Statement Resource
     *
     * @var resource
     */
    protected $statement;

    /**
     * Configuration array
     *
     * @var array
     */
    protected $config = [
        'database' => null,
        'username' => null,
        'password' => null
    ];

    /**
     * Should executions be auto commited?
     *
     * @var boolean
     */
    protected $autoCommit = true;

    /**
     * Constructor
     *
     * @throws \Orno\Db\Exception\UnsupportedException
     * @param  array $config
     */
    public function __construct(array $config = [])
    {
        if (! extension_loaded('oci8')) {
            throw new Exception\UnsupportedException(
                sprintf('%s requires the OCI8 extension to be loaded', __CLASS__)
            );
        }

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\ConnectionException
     * @param  array $config
     * @return \Orno\Db\Driver\Oci8
     */
    public function connect(array $config = [])
    {
        if (is_resource($this->connection)) {
            return $this;
        }

        // filter config array
        $persistent = (isset($config['persistent'])) ? (bool) $config['persistent'] : true;

        $database = (isset($config['database'])) ? $config['database'] : $this->config['database'];
        $username = (isset($config['username'])) ? $config['username'] : $this->config['username'];
        $password = (isset($config['password'])) ? $config['password'] : $this->config['password'];
        $charset  = (isset($config['charset']))  ? $config['charset']  : 'AL32UTF8';

        // intentionally supress errors to catch with oci_error
        $this->connection = ($persistent === true)
                          ? @oci_pconnect($username, $password, $database, $charset)
                          : @oci_new_connect($username, $password, $database, $charset);

        if (! $this->connection) {
            $e = oci_error();
            throw new Exception\ConnectionException($e['message'], $e['code']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return boolean
     */
    public function disconnect()
    {
        // free any outstanding resources as they will not be garbage collected
        if (is_resource($this->statement)) {
            oci_free_statement($this->statement);
        }

        return oci_close($this->connection);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\ConnectionException
     * @param  string $query
     * @return \Orno\Db\Driver\Oci8
     */
    public function prepareQuery($query)
    {
        $this->connect($this->config);

        $this->statement = oci_parse($this->connection, $query);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\BindingException
     * @throws \Orno\Db\Exception\NoResourceException
     * @param  mixed   $placeholder
     * @param  mixed   $value
     * @param  integer $type
     * @param  integer $maxlen
     * @return \Orno\Db\Driver\Oci8
     */
    public function bind($placeholder, $value, $type = self::PARAM_STR, $maxlen = -1)
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared', __METHOD__)
            );
        }

        $type = $this->getValueType($type);

        if (@oci_bind_by_name($this->statement, $placeholder, $value, $maxlen, $type)) {
            return $this;
        }

        // if we've got this far, bail out as the binding has failed
        $e = oci_error($this->statement);
        throw new Exception\BindingException(sprintf($e['message'], $e['code']));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     * @return boolean
     */
    public function execute()
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared', __METHOD__)
            );
        }

        $executed = ($this->isAutoCommit()) ? @oci_execute($this->statement) : @oci_execute($this->statement, OCI_NO_AUTO_COMMIT);

        if ($executed === true) {
            return true;
        }

        $e = oci_error($this->statement);
        throw new Exception\BindingException(sprintf($e['message'], $e['code']));
    }

    /**
     * {@inheritdoc}
     *
     * @return \Orno\Db\Driver\Oci8
     */
    public function transaction()
    {
        $this->connect($this->config);
        $this->setAutoCommit(false);
        return $this;
    }

    /**
     * Set Auto Commit
     *
     * @param  boolean $bool
     * @return void
     */
    protected function setAutoCommit($bool = false)
    {
        $this->autoCommit = (bool) $bool;
    }

    /**
     * Is Auto Commit?
     *
     * Checks if executions should be auto commited
     *
     * @return boolean
     */
    protected function isAutoCommit()
    {
        return (bool) $this->autoCommit;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return oci_commit($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        return oci_rollback($this->connection);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetch()
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        $result = oci_fetch_assoc($this->statement);
        return (empty($result)) ? [] : array_change_key_case($result);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetchObject()
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        $result = oci_fetch_object($this->statement);
        return (empty($result)) ? null : (object) array_change_key_case((array) $result);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetchAll()
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        $result = (oci_fetch_all($this->statement, $result, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC) > 0) ? $result : [];

        array_walk($result, function (&$value) {
            $value = array_change_key_case($value);
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name Name of the sequence object from which the ID should be returned
     * @return null
     */
    public function lastInsertId($name = '')
    {
        // Not implemented!
        return null;
    }

    /**
     * Get Value Type
     *
     * Unify value types accross all drivers
     *
     * @param  integer $value
     * @return integer
     */
    protected function getValueType($type)
    {
        switch ($type) {
            case self::PARAM_STR:
                $type = SQLT_CHR;
                break;
            case self::PARAM_INT:
                $type = SQLT_INT;
                break;
            case self::PARAM_BOOL:
                $type = SQLT_CHR;
                break;
            case self::PARAM_BIN:
                $type = SQLT_BIN;
                break;
            case self::PARAM_FLT:
                $type = SQLT_FLT;
                break;
        }

        return $type;
    }
}
