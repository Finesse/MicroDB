<?php

namespace Finesse\MicroDB\Tests;

use Finesse\MicroDB\DB;
use Finesse\MicroDB\Exceptions\InvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException;

/**
 * Tests the DB class.
 *
 * @author Surgie
 */
class DBTest extends TestCase
{
    /**
     * Tests the create method and the constructor
     */
    public function testCreate()
    {
        $db = DB::create('sqlite::memory:', null, null);
        $this->assertInstanceOf(DB::class, $db);

        $this->assertException(PDOException::class, function () {
            DB::create('foo:bar');
        });
    }

    /**
     * Tests the getPDO method
     */
    public function testGetPDO()
    {
        $db = new DB(new \PDO('sqlite::memory:'));
        $pdo = $db->getPDO();
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertEquals(\PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertEquals(\PDO::FETCH_ASSOC, $pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    /**
     * Tests the bindValue method
     */
    public function testBindValue()
    {
        $db = new DB(new \PDO('sqlite::memory:'));

        /** @var \PDOStatement|\Mockery\MockInterface $pdo */
        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([':param', null, \PDO::PARAM_NULL]);
        $this->invokeMethod($db, 'bindValue', [$statement, ':param', null]);

        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([0, true, \PDO::PARAM_BOOL]);
        $this->invokeMethod($db, 'bindValue', [$statement, 0, true]);

        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([3, false, \PDO::PARAM_BOOL]);
        $this->invokeMethod($db, 'bindValue', [$statement, 3, false]);

        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([':param', 12, \PDO::PARAM_INT]);
        $this->invokeMethod($db, 'bindValue', [$statement, ':param', 12]);

        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([':param', -15.67891, \PDO::PARAM_STR]);
        $this->invokeMethod($db, 'bindValue', [$statement, ':param', -15.67891]);

        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->withArgs([':param', 'Banana', \PDO::PARAM_STR]);
        $this->invokeMethod($db, 'bindValue', [$statement, ':param', 'Banana']);

        $statement = $db->getPDO()->prepare('SELECT :param AS value');
        $this->assertException(InvalidArgumentException::class, function () use ($db, $statement) {
            $this->invokeMethod($db, 'bindValue', [$statement, ':param', [1, 2, 3]]);
        });

    }

    /**
     * Tests the bindValues method
     */
    public function testBindValues()
    {
        /** @var \PDOStatement|\Mockery\MockInterface $pdo */
        $db = new DB(new \PDO('sqlite::memory:'));

        // Anonymous parameters
        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->once()->withArgs([1, 'Foo', \PDO::PARAM_STR]);
        $statement->shouldReceive('bindValue')->once()->withArgs([2, 123, \PDO::PARAM_INT]);
        $statement->shouldReceive('bindValue')->once()->withArgs([3, null, \PDO::PARAM_NULL]);
        $this->invokeMethod($db, 'bindValues', [$statement, ['Foo', 123, null]]);

        // Named parameters
        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->once()->withArgs([':foo', 'Foo', \PDO::PARAM_STR]);
        $statement->shouldReceive('bindValue')->once()->withArgs([':bar', 'Bar', \PDO::PARAM_STR]);
        $this->invokeMethod($db, 'bindValues', [$statement, [':foo' => 'Foo', ':bar' => 'Bar']]);

        // Mixed parameters
        $statement = \Mockery::mock(\PDOStatement::class);
        $statement->shouldReceive('bindValue')->once()->withArgs([1, 'Foo', \PDO::PARAM_STR]);
        $statement->shouldReceive('bindValue')->once()->withArgs([':number', 123, \PDO::PARAM_INT]);
        $statement->shouldReceive('bindValue')->once()->withArgs([3, 'bar', \PDO::PARAM_STR]);
        $this->invokeMethod($db, 'bindValues', [$statement, ['Foo', ':number' => 123, 'bar']]);

        // Wrong arguments
        $statement = $db->getPDO()->prepare('SELECT :param AS value');
        $this->assertException(InvalidArgumentException::class, function () use ($db, $statement) {
            $this->invokeMethod($db, 'bindValues', [$statement, [':param' => new \stdClass()]]);
        });

    }

    /**
     * Tests the select and selectFirst methods
     */
    public function testSelect()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $pdo->exec("
            INSERT INTO `test` (`id`, `name`, `price`)
            VALUES
                (1, 'Row 1', 14.5),
                (2, 'Row 2', 0),
                (3, 'Row 3', 12),
                (4, 'Row 4', -1.67),
                (5, 'Row 5', 201),
                (6, 'Row 6', 44.32312),
                (7, 'Row 7', -12.435),
                (8, 'Row 8', 0.2348729384)
        ");
        $db = new DB($pdo);

        $this->assertCount(8, $db->select('SELECT * FROM test'));
        $this->assertEquals([
            ['id' => 5, 'name' => 'Row 5', 'price' => 201],
            ['id' => 6, 'name' => 'Row 6', 'price' => 44.32312],
        ], $db->select('SELECT * FROM test WHERE price > ? ORDER BY id', [15]));
        $this->assertEquals([
            ['name' => 'Row 7'],
            ['name' => 'Row 4']
        ], $db->select('SELECT name FROM test WHERE price < :price ORDER BY id DESC', [':price' => 0]));
        $this->assertEquals(
            ['id' => 3, 'name' => 'Row 3', 'price' => 12],
            $db->selectFirst('SELECT * FROM test WHERE name = ?', ['Row 3'])
        );
        $this->assertEquals(
            ['id' => 1, 'name' => 'Row 1', 'price' => 14.5],
            $db->selectFirst('SELECT * FROM test ORDER BY id')
        );
        $this->assertNull($db->selectFirst('SELECT * FROM test WHERE name = :name', [ ':name' => 'Foo']));

        // Select using a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->select('I AM NOT A SQL');
        });
        $this->assertException(PDOException::class, function () use ($db) {
            $db->selectFirst('I AM NOT A SQL');
        });
    }

    /**
     * Tests the insert method
     */
    public function testInsert()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $db = new DB($pdo);

        // Insert one row
        $insertedCount = $db->insert(
            'INSERT INTO test (name, price) VALUES (:name, :price)',
            [':name' => 'Baran', ':price' => 456.789]
        );
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(1, $insertedCount);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Baran', 'price' => 456.789]
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Insert some rows
        $insertedCount = $db->insert(
            'INSERT INTO test (name, price) VALUES (?, ?), (?, ?)',
            ['Ovca', 123, 'Wolk', -999]
        );
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(2, $insertedCount);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Baran', 'price' => 456.789],
            ['id' => 2, 'name' => 'Ovca', 'price' => 123],
            ['id' => 3, 'name' => 'Wolk', 'price' => -999],
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Insert using a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->insert('I AM NOT A SQL');
        });
    }

    /**
     * Tests the insertGetId method
     */
    public function testInsertGetId()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $db = new DB($pdo);

        // Insert one row
        $id = $db->insertGetId(
            'INSERT INTO test (name, price) VALUES (:name, :price)',
            [':name' => 'Baran', ':price' => 456.789]
        );
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(1, $id);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Baran', 'price' => 456.789]
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Insert some rows
        $id = $db->insertGetId(
            'INSERT INTO test (name, price) VALUES (?, ?), (?, ?)',
            ['Ovca', 123, 'Wolk', -999]
        );
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(3, $id);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Baran', 'price' => 456.789],
            ['id' => 2, 'name' => 'Ovca', 'price' => 123],
            ['id' => 3, 'name' => 'Wolk', 'price' => -999],
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Insert using a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->insertGetId('I AM NOT A SQL');
        });
    }

    /**
     * Tests the update method
     */
    public function testUpdate()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $pdo->exec("
            INSERT INTO `test` (`id`, `name`, `price`)
            VALUES
                (1, 'Row 1', 14.5),
                (2, 'Row 2', 0),
                (3, 'Row 3', -12)
        ");
        $db = new DB($pdo);

        // Update one row
        $updatedCount = $db->update('UPDATE test SET name = ?, price = ? WHERE id = ?', ['Foo', 42, 2]);
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(1, $updatedCount);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Row 1', 'price' => 14.5],
            ['id' => 2, 'name' => 'Foo', 'price' => 42],
            ['id' => 3, 'name' => 'Row 3', 'price' => -12],
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Update some rows
        $updatedCount = $db->update('UPDATE test SET price = :price', [':price' => 36]);
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(3, $updatedCount);
        $this->assertEquals([
            ['id' => 1, 'name' => 'Row 1', 'price' => 36],
            ['id' => 2, 'name' => 'Foo', 'price' => 36],
            ['id' => 3, 'name' => 'Row 3', 'price' => 36],
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // Update using a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->update('I AM NOT A SQL');
        });
    }

    /**
     * Tests the delete method
     */
    public function testDelete()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $pdo->exec("
            INSERT INTO `test` (`id`, `name`, `price`)
            VALUES
                (1, 'Row 1', 14.5),
                (2, 'Row 2', 0),
                (3, 'Row 3', 12),
                (4, 'Row 4', -1.67),
                (5, 'Row 5', 201),
                (6, 'Row 6', 44.32312),
                (7, 'Row 7', -12.435),
                (8, 'Row 8', 0.2348729384)
        ");
        $db = new DB($pdo);

        // Delete one row
        $updatedCount = $db->delete('DELETE FROM test WHERE id = :id', [':id' => 5]);
        $selectStatement = $pdo->prepare('SELECT id FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(1, $updatedCount);
        $this->assertEquals(
            [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 6], ['id' => 7], ['id' => 8]],
            $selectStatement->fetchAll(\PDO::FETCH_ASSOC)
        );

        // Delete some rows
        $updatedCount = $db->delete('DELETE FROM test WHERE price > ?', [0]);
        $selectStatement = $pdo->prepare('SELECT id FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals(4, $updatedCount);
        $this->assertEquals(
            [['id' => 2], ['id' => 4], ['id' => 7]],
            $selectStatement->fetchAll(\PDO::FETCH_ASSOC)
        );

        // Delete using a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->delete('I AM NOT A SQL');
        });
    }

    /**
     * Tests the statement method
     */
    public function testStatement()
    {
        $pdo = new \PDO('sqlite::memory:');
        $db = new DB($pdo);

        $db->statement('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $db->statement('INSERT INTO test (name, price) VALUES (?, ?)', ['Johny', 991]);

        // Is table created?
        $selectStatement = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='test'");
        $selectStatement->execute();
        $this->assertEquals(['name' => 'test'], $selectStatement->fetch(\PDO::FETCH_ASSOC));

        // Is row created?
        $selectStatement = $pdo->prepare('SELECT * FROM test ORDER BY id');
        $selectStatement->execute();
        $this->assertEquals([
            ['id' => 1, 'name' => 'Johny', 'price' => 991]
        ], $selectStatement->fetchAll(\PDO::FETCH_ASSOC));

        // A statement with a bad query
        $this->assertException(PDOException::class, function () use ($db) {
            $db->statement('I AM NOT A SQL');
        });
    }

    /**
     * Tests mixed parameters keys types (named and anonymous)
     */
    public function testMixedParametersKeys()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $db = new DB($pdo);

        $id = $db->insertGetId('INSERT INTO test VALUES (?, :name, ?)', [1, ':name' => 'A Name', 456]);
        $this->assertEquals(
            ['id' => 1, 'name' => 'A Name', 'price' => 456],
            $db->selectFirst('SELECT * FROM test WHERE id = ?', [$id])
        );

        $id = $db->insertGetId('INSERT INTO test VALUES (:id, ?, :price)', [':id' => 2, 'Bill', ':price' => -12]);
        $this->assertEquals(
            ['id' => 2, 'name' => 'Bill', 'price' => -12],
            $db->selectFirst('SELECT * FROM test WHERE id = ?', [$id])
        );
    }

    /**
     * Tests that query methods throw proper exception on an invalid parameters argument
     */
    public function testInvalidParameters()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
        $db = new DB($pdo);

        $this->assertException(InvalidArgumentException::class, function () use ($db) {
            $db->insert('INSERT INTO test VALUES (?, ?, ?)', [[1, 'Name', 111]]);
        });

    }
}
