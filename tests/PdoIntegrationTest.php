<?php namespace OrnoTest;

use Orno\Db\Driver\Pdo;

class PdoIntegrationTest extends \PHPUnit_Framework_TestCase
{
    protected $config = [
        'database' => 'PDO_DATABASE',
        'username' => 'PDO_USERNAME',
        'password' => 'PDO_PASSWORD'
    ];

    protected $driver;

    protected $staged = true;

    public function setUp()
    {
        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('The PDO extension is not loaded and therefore cannot be integration tested');
        }

        foreach ($this->config as $key => $val) {
            if (! isset($GLOBALS[$val])) {
                $this->staged = false;
                $this->markTestSkipped('Missing required config variable ' . $val . ' from phpunit.xml');
            }

            $this->config[$key] = $GLOBALS[$val];
        }

        $this->driver = new Pdo($this->config);
    }

    public function tearDown()
    {
        if (extension_loaded('pdo') && $this->staged === true) {
            @$this->driver->prepareQuery('DROP TABLE test_data');
            @$this->driver->execute();
            @$this->driver->disconnect();
        }

        unset($this->driver);
    }

    public function testConnectsAndDisconnects()
    {
        $this->driver->connect();
        $this->assertTrue($this->readAttribute($this->driver, 'connection') instanceof \PDO);
        $this->driver->disconnect();
        $this->assertFalse($this->readAttribute($this->driver, 'connection') instanceof \PDO);
    }

    public function testConnectionFailsWithIncorrectCredentials()
    {
        $this->setExpectedException('Orno\Db\Exception\ConnectionException');
        $this->driver->connect(['database' => 'WRONG_DB_STRING']);
    }

    public function testPreparesQuery()
    {
        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->assertTrue($this->readAttribute($this->driver, 'statement') instanceof \PDOStatement);
        $this->driver->disconnect();
        $this->assertFalse($this->readAttribute($this->driver, 'statement') instanceof \PDOStatement);
    }

    public function testBindingThrowsExceptionWithoutStatement()
    {
        $this->setExpectedException('Orno\Db\Exception\NoResourceException');
        $this->driver->bind(':placeholder', 'value');
    }

    public function testBindingParameter()
    {
        $this->driver->prepareQuery('SELECT * FROM test_data WHERE placeholder = :placeholder AND placeholder2 = :placeholder2');
        $this->assertSame($this->driver, $this->driver->bind(':placeholder', 'value'));
        $this->assertSame($this->driver, $this->driver->bind(':placeholder2', 'value', \PDO::PARAM_STR, 5));
    }

    public function testExecuteThrowsExceptionWithoutStatement()
    {
        $this->setExpectedException('Orno\Db\Exception\NoResourceException');
        $this->driver->execute();
    }

    public function testAutoCommitExecutesAndFetchAll()
    {
        $this->driver->prepareQuery('CREATE TABLE test_data (username varchar(100), email varchar(100))');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->driver->prepareQuery('INSERT INTO test_data VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        $this->assertSame($this->getInitialData(), $this->driver->fetchAll());
    }

    public function testTransactionCommits()
    {
        $this->driver->transaction();

        $this->driver->prepareQuery('CREATE TABLE test_data (username varchar(100), email varchar(100))');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->driver->prepareQuery('INSERT INTO test_data VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->commit();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        $this->assertSame($this->getInitialData(), $this->driver->fetchAll());

        $this->driver->transaction();

        foreach ($this->getUpdatedData() as $data) {
            $this->driver->prepareQuery('UPDATE test_data SET username = :username WHERE email = :email');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->prepareQuery('DELETE FROM test_data WHERE username NOT LIKE :username');
        $this->driver->bind(':username', '%_updated');
        $this->driver->execute();

        $this->driver->commit();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        $this->assertSame($this->getUpdatedData(), $this->driver->fetchAll());
    }

    public function testTransactionRollsBack()
    {
        $this->driver->transaction();

        $this->driver->prepareQuery('CREATE TABLE test_data (username varchar(100), email varchar(100))');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->driver->prepareQuery('INSERT INTO test_data VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->commit();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        $this->assertSame($this->getInitialData(), $this->driver->fetchAll());

        $this->driver->transaction();

        foreach ($this->getUpdatedData() as $data) {
            $this->driver->prepareQuery('UPDATE test_data SET username = :username WHERE email = :email');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->prepareQuery('DELETE FROM test_data WHERE username NOT LIKE :username');
        $this->driver->bind(':username', '%_updated');
        $this->driver->execute();

        $this->driver->rollback();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        $this->assertSame($this->getInitialData(), $this->driver->fetchAll());
    }

    public function testFetchThrowsExceptionWithoutStatement()
    {
        $this->setExpectedException('Orno\db\Exception\NoResourceException');
        $this->driver->fetch();
    }

    public function testFetchBringsBackRowByRow()
    {
        $this->driver->transaction();

        $this->driver->prepareQuery('CREATE TABLE test_data (username varchar(100), email varchar(100))');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->driver->prepareQuery('INSERT INTO test_data VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->commit();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->assertSame($data, $this->driver->fetch());
        }
    }

    public function testFetchAllThrowsExceptionWithoutStatement()
    {
        $this->setExpectedException('Orno\db\Exception\NoResourceException');
        $this->driver->fetchAll();
    }

    public function testFetchObjectThrowsExceptionWithoutStatement()
    {
        $this->setExpectedException('Orno\db\Exception\NoResourceException');
        $this->driver->fetchObject();
    }

    public function testFetchObjectBringsBackRowByRow()
    {
        $this->driver->transaction();

        $this->driver->prepareQuery('CREATE TABLE test_data (username varchar(100), email varchar(100))');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $this->driver->prepareQuery('INSERT INTO test_data VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->driver->commit();

        $this->driver->prepareQuery('SELECT * FROM test_data');
        $this->driver->execute();

        foreach ($this->getInitialData() as $data) {
            $row = $this->driver->fetchObject();
            $this->assertSame($data['username'], $row->username);
            $this->assertSame($data['email'], $row->email);
        }
    }

    public function testLastInsertId()
    {
        $this->driver->prepareQuery(
            'CREATE TABLE test_data(
                id int(1) NOT NULL AUTO_INCREMENT,
                username varchar(100),
                email varchar(100),
                PRIMARY KEY (id)
            )'
        );
        $this->driver->execute();

        $initialData = $this->getInitialData();
        foreach ($initialData as $id => $data) {
            $this->driver->prepareQuery('INSERT INTO test_data (`username`, `email`) VALUES (:username, :email)');
            $this->driver->bind(':username', $data['username']);
            $this->driver->bind(':email', $data['email']);
            $this->driver->execute();
        }

        $this->assertSame((string) count($initialData), $this->driver->lastInsertId());
    }

    public function getInitialData()
    {
        return [
            ['username' => 'pbenn', 'email' => 'pbenn@example.com'],
            ['username' => 'posbo', 'email' => 'posbo@example.com'],
            ['username' => 'mbard', 'email' => 'mbard@example.com'],
            ['username' => 'jfrye', 'email' => 'jfrye@example.com'],
            ['username' => 'slang', 'email' => 'slang@example.com']
        ];
    }

    public function getUpdatedData()
    {
        return [
            ['username' => 'pbenn_updated', 'email' => 'pbenn@example.com'],
            ['username' => 'posbo_updated', 'email' => 'posbo@example.com'],
            ['username' => 'mbard_updated', 'email' => 'mbard@example.com']
        ];
    }
}
