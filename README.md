# Orno\Db [In Development]

[![Build Status](https://travis-ci.org/orno/db.png?branch=master)](https://travis-ci.org/orno/db)

A simple database abstraction layer.

### Support

- Oracle OCI8
- PDO

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
