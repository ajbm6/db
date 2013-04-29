<?php namespace OrnoTest;

use Orno\Db\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesDriverObject()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');
        $q = new Query($driver);

        $this->assertInstanceOf('Orno\Db\Query', $q);
        $this->assertInstanceOf('Orno\Db\Driver\Pdo', $q->getDriver());

        $q->setDriver($driver);
        $this->assertInstanceOf('Orno\Db\Driver\Pdo', $q->getDriver());
    }

    public function testPreparesQuery()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('prepareQuery')
               ->with($this->equalTo('SELECT * FROM test_table'))
               ->will($this->returnValue($driver));

        $q = new Query($driver);

        $this->assertInstanceOf('Orno\Db\Query', $q->prepare('SELECT * FROM test_table'));
    }

    public function testPreparesQueryWithOverrideConfig()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $config = [
            'database' => 'database',
            'username' => 'username',
            'password' => 'password'
        ];

        $driver->expects($this->once())
               ->method('prepareQuery')
               ->with($this->equalTo('SELECT * FROM test_table'))
               ->will($this->returnValue($driver));

        $driver->expects($this->once())
               ->method('connect')
               ->with($this->equalTo($config))
               ->will($this->returnValue($driver));

        $q = new Query($driver, $config);

        $this->assertInstanceOf('Orno\Db\Query', $q->prepare('SELECT * FROM test_table'));
    }

    public function testBindsParameter()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('bind')
               ->with($this->equalTo(':placeholder'), $this->equalTo('value'))
               ->will($this->returnValue($driver));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->bind(':placeholder', 'value'));
    }

    public function testBindsParameterWithType()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('bind')
               ->with($this->equalTo(':placeholder'), $this->equalTo('value'), $this->equalTo(\PDO::PARAM_STR))
               ->will($this->returnValue($driver));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->bind(':placeholder', 'value', \PDO::PARAM_STR));
    }

    public function testExecutesQuery()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('execute')
               ->will($this->returnValue(true));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->execute());
    }

    public function testStartsTransaction()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('transaction')
               ->will($this->returnValue($driver));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->transaction());
    }

    public function testStartsTransactionWithOverrideConfig()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $config = [
            'database' => 'database',
            'username' => 'username',
            'password' => 'password'
        ];


        $driver->expects($this->once())
               ->method('transaction')
               ->will($this->returnValue($driver));

        $driver->expects($this->once())
               ->method('connect')
               ->with($this->equalTo($config))
               ->will($this->returnValue($driver));

        $q = new Query($driver, $config);
        $this->assertInstanceOf('Orno\Db\Query', $q->transaction());
    }

    public function testCommitsTransaction()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('commit')
               ->will($this->returnValue($driver));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->commit());
    }

    public function testRollsBackTransaction()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $driver->expects($this->once())
               ->method('rollback')
               ->will($this->returnValue($driver));

        $q = new Query($driver);
        $this->assertInstanceOf('Orno\Db\Query', $q->rollback());
    }

    public function testFetchesRowArray()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $row = [
            'id'   => 1,
            'col1' => 'value',
            'col2' => 'value2'
        ];

        $driver->expects($this->once())
               ->method('fetch')
               ->will($this->returnValue($row));

        $q = new Query($driver);
        $this->assertSame($row, $q->fetch());
    }

    public function testFetchesRowObject()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $row = new \stdClass;

        $row->id = 1;
        $row->col1 = 'value';
        $row->col2 = 'value2';

        $driver->expects($this->once())
               ->method('fetchObject')
               ->will($this->returnValue($row));

        $q = new Query($driver);
        $this->assertSame($row, $q->fetchObject());
    }

    public function testFetchesArrayOfAllResults()
    {
        $driver = $this->getMock('Orno\Db\Driver\Pdo');

        $results = [
            [
                'id'   => 1,
                'col1' => 'value',
                'col2' => 'value2'
            ],
            [
                'id'   => 2,
                'col1' => 'value',
                'col2' => 'value2'
            ],
            [
                'id'   => 3,
                'col1' => 'value',
                'col2' => 'value2'
            ]
        ];

        $driver->expects($this->once())
               ->method('fetchAll')
               ->will($this->returnValue($results));

        $q = new Query($driver);
        $this->assertSame($results, $q->fetchAll());
    }
}
