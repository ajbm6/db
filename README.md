# Orno\Db

[![Latest Version](http://img.shields.io/packagist/v/orno/db.svg?style=flat)](https://packagist.org/packages/orno/db)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/orno/db/master.svg?style=flat)](https://travis-ci.org/orno/db)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/orno/db.svg?style=flat)](https://scrutinizer-ci.com/g/orno/db/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/orno/db.svg?style=flat)](https://scrutinizer-ci.com/g/orno/db)
[![Total Downloads](https://img.shields.io/packagist/dt/orno/db.svg?style=flat)](https://packagist.org/packages/orno/db)

A simple database abstraction layer. It provides a unified API to enable you to write ANSI/ISO SQL that will be handled in the same way, regardless of your database driver.

### Support

- Oracle OCI8
- PDO

### Planned

- MySQLi
- SQLite
- MSSQL
- PostgreSQL

## Basic Usage

### Simple Statements

```php
<?php

$config = [
    'database' => 'mysql:dbname=database;host=localhost;charset=utf8',
    'username' => 'root',
    'password' => 'password'
];

$driver = new Orno\Db\Driver\Pdo($config);
$query = new Orno\Db\Query($driver);

$query->prepare('SELECT field_name, field_name2, field_name3 FROM some_table');
$query->execute();

while ($row = $query->fetch()) {
    echo $row['field_name'] . '<br>';
    echo $row['field_name2'] . '<br>';
    echo $row['field_name3'] . '<br>';
}
```

### Binding Parameters

Binding parameters handles all sanitisation for you and guards against any SQL injection.

```php
<?php

$config = [
    'database' => 'ORACLE_CONNECTION_STRING',
    'username' => 'username',
    'password' => 'password'
];

$driver = new Orno\Db\Driver\Oci8($config);
$query = new Orno\Db\Query($driver);

$query->prepare('SELECT field_name, field_name2, field_name3
                 FROM some_table
                 WHERE field_name = :some_value
                 AND field_name2 = :some_other_value');

$query->bind(':some_value', 'some_value')
      ->bind(':some_other_value', 88, Orno\Db\Query::PARAM_INT);

$query->execute();

while ($row = $query->fetchObject()) {
    echo $row->field_name . '<br>';
    echo $row->field_name2 . '<br>';
    echo $row->field_name3 . '<br>';
}
```

### Transactions

To start a transaction, call the `transaction` method, run multiple queries and executes, then either `commit` or `rollback` your transaction.

#### Commiting

```php
$query->transaction();

$query->prepare('INSERT ...');
// bind any parameters
$query->execute();

$query->prepare('UPDATE ...');
// bind any parameters
$query->execute();

$query->commit();
```

#### Rolling Back

```php
$query->transaction();

$query->prepare('INSERT ...');
// bind some parameters
$query->execute();

$query->prepare('UPDATE ...');
// bind some parameters
$query->execute();

$query->prepare('SELECT ...');
$query->execute();
$query->fetchAll();

// if some conditions are met then commit
// else rollback to the point before the transaction began
if (/* .. */) {
    $query->commit();
} else {
    $query->rollback();
}
```

## Testing Notes

Orno\Db is well unit tested but also has integration tests, the integration tests require the ability to touch the database and therefore need connection credentials to do so. Below are instructions on how to run the integration tests.

### Oracle Integration Tests

Add the following to the `phpunit.xml.dist` configuration file to enable **Oracle** integration testing.

    <php>
        <var name="OCI8_DATABASE" value="CONNECTION_STRING" />
        <var name="OCI8_USERNAME" value="username" />
        <var name="OCI8_PASSWORD" value="password" />
    </php>

### PDO Integration Tests

Add the following to the `phpunit.xml.dist` configuration file to enable **PDO** integration testing.

    <php>
        <var name="PDO_DATABASE" value="CONNECTION_STRING" />
        <var name="PDO_USERNAME" value="username" />
        <var name="PDO_PASSWORD" value="password" />
    </php>

