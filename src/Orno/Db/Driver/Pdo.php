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
 * PDO Driver
 *
 * A database abstraction for PHP PDO driver
 */
class Pdo implements DriverInterface
{
    /**
     * The PDO object
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * The PDO statement object
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * Constructor
     *
     * @throws \Orno\Db\Exception\UnsupportedException
     * @param  array $config
     */
    public function __construct(array $config = [])
    {
        if (! extension_loaded('pdo')) {
            throw new Exception\UnsupportedException(
                sprintf('%s requires the PDO extension to be loaded', __CLASS__)
            );
        }

        $this->connect($config);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\ConnectionException
     * @param  array $config
     * @return \Orno\Db\Driver\Pdo
     */
    public function connect(array $config = [])
    {
        $options = (isset($config['options'])) ? $config['options'] : [];

        $options[\PDO::ATTR_PERSISTENT] = (isset($config['persistent'])) ? (bool) $config['persistent'] : true;

        $database = (isset($config['database'])) ? $config['database'] : null;
        $username = (isset($config['username'])) ? $config['username'] : null;
        $password = (isset($config['password'])) ? $config['password'] : null;
        $charset  = (isset($config['charset']))  ? $config['charset']  : 'UTF8';

        try {
            $this->connection = new \PDO($database, $username, $password, $options);

            // prior to 5.3.6 the charset key in the connection string is ignored
            // so we can check the PHP version and force charset this way
            if (strnatcmp(phpversion(), '5.3.6') < 0) {
                $this->connection->exec("SET NAMES $charset");
            }
        } catch (\PDOException $e) {
            throw new Exception\ConnectionException($e->getMessage, $e->getCode);
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
        unset($this->connection);
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\ConnectionException
     * @param  string $query
     * @return \Orno\Db\Driver\Pdo
     */
    public function prepareQuery($query)
    {
        try {
            $this->statement = $this->connection->prepare($query);
        } catch (\PDOException $e) {
            throw new Exception\QueryException($e->getMessage(), $e->getCode());
        }

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
     * @return \Orno\Db\Driver\Pdo
     */
    public function bind($placeholder, $value, $type = \PDO::PARAM_STR, $maxlen = 0)
    {
        if (! $this->statement instanceof \PDOStatement) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared', __METHOD__)
            );
        }

        try {
            if ($maxlen > 0) {
                $this->statement->bindParam($placeholder, $value, $type, (int) $maxlen);
            } else {
                $this->statement->bindParam($placeholder, $value, $type);
            }
        } catch (\PDOException $e) {
            throw new Exception\BindingException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     * @return boolean
     */
    public function execute()
    {
        if (! $this->statement instanceof \PDOStatement) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared', __METHOD__)
            );
        }

        return $this->statement->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Orno\Db\Driver\Pdo
     */
    public function transaction()
    {
        $this->connection->beginTransaction();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetch()
    {
        if (! $this->statement instanceof \PDOStatement) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        return $this->statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetchObject()
    {
        if (! $this->statement instanceof \PDOStatement) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        return $this->statement->fetchObject();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Orno\Db\Exception\NoResourceException
     */
    public function fetchAll()
    {
        if (! $this->statement instanceof \PDOStatement) {
            throw new Exception\NoResourceException(
                sprintf('%s expects a query to have been prepared and executed', __METHOD__)
            );
        }

        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
