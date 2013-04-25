<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 */
namespace Orno\Db\Driver;

/**
 * Driver Interface
 *
 * Contract to which all database drivers should conform
 */
interface DriverInterface
{
    /**
     * Connect
     *
     * Connect to the database
     *
     * @param array $config
     */
    public function connect(array $config = []);

    /**
     * Disconnect
     *
     * Kill the connection to the database
     */
    public function disconnect();

    /**
     * Prepare Query
     *
     * Prepare a query statement
     *
     * @param string $query
     */
    public function prepareQuery($query);

    /**
     * Bind Param
     *
     * Bind the value of a referenced variable to a placeholder in the prepared query
     *
     * @param string  $placeholder
     * @param mixed   &$value - Referenced variable
     * @param integer $type
     * @param integer $maxlen
     */
    public function bindParam($placeholder, &$value, $type, $maxlen);

    /**
     * Bind Value
     *
     * Bind an actual value to a placeholder in the prepared query
     *
     * @param string  $placeholder
     * @param mixed   $value
     * @param integer $type
     */
    public function bindValue($placeholder, $value, $type);

    /**
     * Execute
     *
     * Execute a prepared query
     */
    public function execute();

    /**
     * Transaction
     *
     * Begin a transaction
     *
     * @return boolean
     */
    public function transaction();

    /**
     * Commit
     *
     * Commit all executions made in the current transaction
     *
     * @return boolean
     */
    public function commit();

    /**
     * Rollback
     *
     * Rollback all executions made in the current transaction
     *
     * @return boolean
     */
    public function rollback();

    /**
     * Fetch
     *
     * Return an associative array of the next row in the result set
     *
     * <code>
     * [
     *     $column1 => $value1,
     *     $column2 => $value2
     * ]
     * </code>
     *
     * @return array
     */
    public function fetch();

    /**
     * Fetch Object
     *
     * Return a stdClass object of the next row in the result set
     *
     * @return object
     */
    public function fetchObject();

    /**
     * Fetch All
     *
     * Return an indexed array containing associative arrays of single rows
     *
     * <code>
     * [
     *     0 => [
     *         $column1 => $value1,
     *         $column2 => $value2
     *     ],
     *     1 => [
     *         $column1 => $value1,
     *         $column2 => $value2
     *     ]
     * ]
     * </code>
     *
     * @return array
     */
    public function fetchAll();
}
