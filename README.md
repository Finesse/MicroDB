# MicroDB

[![Latest Stable Version](https://poser.pugx.org/finesse/micro-db/v/stable)](https://packagist.org/packages/finesse/micro-db)
[![Total Downloads](https://poser.pugx.org/finesse/micro-db/downloads)](https://packagist.org/packages/finesse/micro-db)
![PHP from Packagist](https://img.shields.io/packagist/php-v/finesse/micro-db.svg)
[![Test Status](https://github.com/finesse/MicroDB/workflows/Test/badge.svg)](https://github.com/Finesse/MicroDB/actions?workflow=Test)
[![Maintainability](https://api.codeclimate.com/v1/badges/f4d3bcbd54c012ef4eaf/maintainability)](https://codeclimate.com/github/Finesse/MicroDB/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/f4d3bcbd54c012ef4eaf/test_coverage)](https://codeclimate.com/github/Finesse/MicroDB/test_coverage)

Like to use pure SQL but don't like to suffer from PDO, mysqli or etc.? Try this.

```php
$database = Connection::create('mysql:host=localhost;dbname=my_database', 'user', 'pass');
$items = $database->select('SELECT * FROM items WHERE category_id = ?', [3]);
```

Key features:

* No silly query builder, only a good old SQL.
* Very light, no external dependencies.
  It required only the [PDO extension](http://php.net/manual/en/book.pdo.php) which is available by default in most of servers.
* Database object is delivered explicitly, not through a static class.
* Exceptions on errors.

You can combine it with a third-party SQL query builder to rock the database. Examples of suitable query builders:
[Query Scribe](https://github.com/Finesse/QueryScribe),
[Nilportugues SQL Query Builder](https://github.com/nilportugues/php-sql-query-builder), 
[Aura.SqlQuery](https://github.com/auraphp/Aura.SqlQuery),
[Latitude](https://github.com/shadowhand/latitude),
[Koine Query Builder](https://github.com/koinephp/QueryBuilder),
[Phossa2 Query](https://github.com/phossa2/query),
[Hydrahon](https://github.com/ClanCats/Hydrahon).


## Installation

### Using [Composer](https://getcomposer.org)

Run in a console

```bash
composer require finesse/micro-db
```


## Reference

### Create a `Connection` instance

To create a new `Connection` instance call the `create` method passing 
[PDO constructor arguments](http://php.net/manual/en/pdo.construct.php).

```php
use Finesse\MicroDB\Connection;

$database = Connection::create('dsn:string', 'username', 'password, ['options']);
```

Or pass a `PDO` instance to the constructor. But be careful: `Connection` _changes_ the given `PDO` object and you 
_must not_ change the given object, otherwise something unexpected will happen.

```php
use Finesse\MicroDB\Connection;

$pdo = new PDO(/* ... */);
$database = new Connection($pdo);
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

The cell values are returned as they are returned by PDO. They are not casted automatically because casting can cause 
data loss.

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

If the query contains multiple statements separated by a semicolon, only the first statement will be executed. You can
execute multiple statements using the other method:

```php
$database->statements("
    CREATE TABLE table(id INTEGER PRIMARY KEY ASC, name TEXT, price NUMERIC);
    INSERT INTO table (name, price) VALUES ('Donald', 1000000);
");
```

The lack of this method is that it doesn't take values to bind.

### Execute a file

Execute the query from an SQL file:

```php
$database->import('path/to/file.sql');
```

Or from a resource:

```php
$stream = fopen('path/to/file.sql', 'r');
$database->import($stream);
```

### Binding values

You should not insert values right to an SQL query because it can cause 
[SQL injections](https://en.wikipedia.org/wiki/SQL_injection). Instead use the binding:

```php
// WRONG! Don't do it or you will be fired
$rows = $database->select("SELECT * FROM table WHERE name = '$name' LIMIT $limit");

// Good
$rows = $database->select('SELECT * FROM table WHERE name = ? LIMIT ?', [$name, $limit]);
```

Database server replaces the placeholders (`?`s) safely with the given values. Almost all the above methods accepts 
the list of the bound values as the second argument.

You can also use named parameters:

```php
$rows = $database->select('SELECT * FROM table WHERE name = :name LIMIT :limit', [':name' => $name, ':limit' => $limit]);
```

You can even pass named and anonymous parameters in the same array but it works only when the array of values has the 
same order as the placeholders in the query text.

All the scalar types of values are supported: string, integer, float, boolean and null.

### Error handling

The `Finesse\MicroDB\Exceptions\PDOException` is thrown in case of every database query error. If an error is caused
by an SQL query, the exception has the query text and bound values in the message. They are also available through the
methods:

```php
$sql = $exception->getQuery();
$bindings = $exception->getValues();
``` 

The `Finesse\MicroDB\Exceptions\InvalidArgumentException` is thrown when the method arguments have a wrong format.

The `Finesse\MicroDB\Exceptions\FileException` is thrown on a file read error.

All the exceptions implement `Finesse\MicroDB\IException`.

### Retrieve the underlying `PDO` object

```php
$pdo = $database->getPDO();
```

You _must not_ change the retrieved object, otherwise something unexpected will happen.


## Known problems

* `insertGetId` doesn't return the inserted row identifier for SQL Server and PostgreSQL.
* `statements` and `import` don't throw an exception if the second or a next statement of the query has an error. This 
  is [a PDO bug](https://stackoverflow.com/a/28867491/1118709).

Make a pull request or an issue if you need a problem to be fixed.


## Versions compatibility

The project follows the [Semantic Versioning](http://semver.org).


## License

MIT. See [the LICENSE](LICENSE) file for details.
