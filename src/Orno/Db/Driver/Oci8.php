<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
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
 * will reduce the number of round trips on the network by buffering rows into the SLQ*Net
 * transport cache.
 */
class Oci8 implements DriverInterface
{
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
     * Should executions be auto commited?
     *
     * @var boolean
     */
    protected $autoCommit = false;

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

        $this->connect($config);
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
        // filter config array
        $persistent = (isset($config['persistent'])) ? $config['persistent'] : true;
        $database   = (isset($config['database']))   ? $config['database']   : null;
        $username   = (isset($config['username']))   ? $config['username']   : null;
        $password   = (isset($config['password']))   ? $config['password']   : null;

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
        if ($this->statement = oci_parse($this->connection, $query)) {
            return $this;
        }

        $e = oci_error($this->connection);
        throw new Exception\QueryException($e['message'], $e['code']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\BindingException
     * @throws \Orno\Db\Exception\NoResourceException
     * @param  string  $placeholder
     * @param  mixed  &$value
     * @param  integer $type
     * @param  integer $maxlen
     * @return \Orno\Db\Driver\Oci8
     */
    public function bindParam($placeholder, &$value, $type = SQLT_CHR, $maxlen = -1)
    {
        if (! is_resource($this->statement)) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared', __METHOD__)
            );
        }

        if (oci_bind_by_name($this->statement, $placeholder, $value, $maxlen, $type)) {
            return $this;
        }

        // if we've got this far, bail out as the binding has failed
        $e = oci_error($this->statement);
        throw new Exception\BindingException(sprintf($e['message'], $e['code']));
    }

    /**
     * Bind Value
     *
     * Proxies to bind param but allows a value to be passed rather than a reference
     * to a variable
     *
     * @param string  $placeholder
     * @param mixed   $value
     * @param integer $type
     * @return \Orno\Db\Driver\Oci8
     */
    public function bindValue($placeholder, $value, $type = SQLT_CHR)
    {
        return $this->bindParam($placeholder, $value, $type);
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

        return ($this->isAutoCommit()) ? oci_execute($this->statement) : oci_execute($this->statement, OCI_NO_AUTO_COMMIT);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Orno\Db\Driver\Oci8
     */
    public function transaction()
    {
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

        return oci_fetch_assoc($this->statement);
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

        return oci_fetch_object($this->statement);
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

        return (oci_fetch_all($this->statement, $result, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC) > 0) ? $result : [];
    }
}
