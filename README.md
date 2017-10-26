# MicroDB

[![Latest Stable Version](https://poser.pugx.org/finesse/micro-db/v/stable)](https://packagist.org/packages/finesse/micro-db)
[![Total Downloads](https://poser.pugx.org/finesse/micro-db/downloads)](https://packagist.org/packages/finesse/micro-db)
[![Build Status](https://php-eye.com/badge/finesse/micro-db/tested.svg)](https://travis-ci.org/FinesseRus/MicroDB)
[![Coverage Status](https://coveralls.io/repos/github/FinesseRus/MicroDB/badge.svg?branch=master)](https://coveralls.io/github/FinesseRus/MicroDB?branch=master)
[![Dependency Status](https://www.versioneye.com/php/finesse:micro-db/badge)](https://www.versioneye.com/php/finesse:micro-db)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/86ca4104-f2d4-4064-a0d3-bba5a4aa2fe2/mini.png)](https://insight.sensiolabs.com/projects/86ca4104-f2d4-4064-a0d3-bba5a4aa2fe2)

Like to use pure SQL but don't like to suffer with PDO, mysqli and etc.? Try this.

```php
$database = DB::create('mysql:host=localhost;dbname=my_database', 'user', 'pass');
$items = $database->select('SELECT * FROM items WHERE category_id = ?', [3]);
```

You can combine it with a third-party SQL query builder to rock the database.

Key features:

* No silly query builder, only clear SQL.
* No external dependencies.
  It required only the [PDO extension](http://php.net/manual/en/book.pdo.php) which is available by default in most of servers.
* Exceptions on errors.


## Installation

### Using [composer](https://getcomposer.org)

Run in a console

```bash
composer require finesse/micro-db
```


## Reference

### Create a `DB` instance

To create a new `DB` instance call the `create` method passing 
[PDO constructor arguments](http://php.net/manual/en/pdo.construct.php).

```php
use Finesse\MicroDB\DB;

$database = DB::create('dns:string', 'username', 'password, ['options']);
```

Or pass a `PDO` instance to the constructor. But be careful: `DB` _changes_ the given `PDO` object and you _must not_ change the
given object, otherwise something unexpected will happen.

```php
use Finesse\MicroDB\DB;

$pdo = new PDO(/* ... */);
$database = new DB($pdo);
```

### Select

Select many rows:

```php
$rows = $database->select('SELECT * FROM table'); // [['id' => 1, 'name' => 'Bill'], ['id' => 2, 'name' => 'John']]
```

Select one row:

```php
$row = $database->selectFirst('SELECT * FROM table'); // ['id' => 1, 'name' => 'Bill']
```

### Insert

Insert and get the number of the inserted rows:

```php
$insertedCount = $database->insert('INSERT INTO table (id, price) VALUES (1, 45), (2, 98)'); // 2
```

Insert and get the identifier of the last inserted row:

```php
$id = $database->insertGetId('INSERT INTO table (weight, price) VALUES (12.3, 45)'); // 3
```

### Update

Update rows and get the number of the updated rows:

```php
$updatedCount = $database->update('UPDATE table SET status = 1 WHERE price < 1000');
```

### Delete

Delete rows and get the number of the deleted rows:

```php
$deletedCount = $database->delete('DELETE FROM table WHERE price > 1000');
```

### Other queries

Perform any other statement:

```php
$database->statement('CREATE TABLE table(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC)');
```

The method returns nothing.

### Binding values

You should not insert values right to a SQL query because it can cause 
[SQL injections](https://en.wikipedia.org/wiki/SQL_injection). Instead use the binding:

```php
// WRONG! Don't do it or you will be fired
$rows = $database->select('SELECT * FROM table WHERE name = '.$name.' LIMIT '.$limit);

// Good
$rows = $database->select('SELECT * FROM table WHERE name = ? LIMIT ?', [$name, $limit]);
```

Placeholders (`?`s) are safely replaced by the given values by the database server. All the above methods accept the 
list of the bound values as the second argument.

You can also use named parameters:

```php
$rows = $database->select('SELECT * FROM table WHERE name = :name LIMIT :limit', [':name' => 'Jack', ':limit' => 10]);
```

You can even pass mixed parameters but it works only when the array of values has the same order as 
the placeholders in the query text.

All scalar types of values are supported: string, integer, float, boolean and null.

### Error handling

The `Finesse\MicroDB\Exceptions\PDOException` is thrown in case of every database query error.

The `Finesse\MicroDB\Exceptions\InvalidArgumentException` is thrown when the method arguments have a wrong format.

All exceptions implement `Finesse\MicroDB\IException`.


## Known problems

* `insertGetId` doesn't return the inserted row identifier for SQL server and Postgres.

Make a pull request or an issue if you need a problem to be fixed.


## Versions compatibility

The project follows the [Semantic Versioning](http://semver.org).


## License

MIT. See [the LICENSE](LICENSE) file for details.

