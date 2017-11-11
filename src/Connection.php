<?php

namespace Finesse\MicroDB;

use Finesse\MicroDB\Exceptions\FileException;
use Finesse\MicroDB\Exceptions\InvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException;
use PDOException as BasePDOException;

/**
 * Wraps a PDO object for more convenient usage.
 *
 * Future features:
 *  * todo: transations
 *  * todo: PostgreSQL and SQL Server support
 *
 * @author Surgie
 */
class Connection
{
    /**
     * @var \PDO The PDO instance. Throws exceptions on errors. The default fetch mode is FETCH_ASSOC.
     */
    protected $pdo;

    /**
     * Connection constructor.
     *
     * @param \PDO $pdo A PDO instance to work with. The given object WILL BE MODIFIED. You MUST NOT MODIFY the given
     *     object.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    /**
     * Creates a self instance. All the arguments are the arguments for the PDO constructor.
     *
     * @param string $dsn
     * @param string|null $username
     * @param string|null $passwd
     * @param array|null $options
     * @return static
     * @throws PDOException
     * @see http://php.net/manual/en/pdo.construct.php Arguments reference
     */
    public static function create(
        string $dsn,
        string $username = null,
        string $passwd = null,
        array $options = null
    ): self {
        $defaultOptions = [
            \PDO::ATTR_STRINGIFY_FETCHES => false
        ];

        try {
            return new static(new \PDO($dsn, $username, $passwd, array_replace($defaultOptions, $options ?? [])));
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a select query and returns the query results.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function select(string $query, array $values = []): array
    {
        try {
            return $this->executeStatement($query, $values)->fetchAll();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs a select query and returns the first query result.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function selectFirst(string $query, array $values = [])
    {
        try {
            $row = $this->executeStatement($query, $values)->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs an insert query and returns the number of inserted rows.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function insert(string $query, array $values = []): int
    {
        try {
            return $this->executeStatement($query, $values)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs an insert query and returns the identifier of the last inserted row.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @param string|null $sequence Name of the sequence object from which the ID should be returned
     * @return int|string
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function insertGetId(string $query, array $values = [], string $sequence = null)
    {
        try {
            $this->executeStatement($query, $values);
            $id = $this->pdo->lastInsertId($sequence);
            return is_numeric($id) ? (int)$id : $id;
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs an update query.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int The number of updated rows
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function update(string $query, array $values = []): int
    {
        try {
            return $this->executeStatement($query, $values)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs a delete query.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int The number of deleted rows
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function delete(string $query, array $values = []): int
    {
        try {
            return $this->executeStatement($query, $values)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs a general query. If the query contains multiple statements separated by a semicolon, only the first
     * statement will be executed.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function statement(string $query, array $values = [])
    {
        try {
            $this->executeStatement($query, $values);
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query, $values);
        }
    }

    /**
     * Performs a general query. It executes all the statements separated by a semicolon.
     *
     * @param string $query Full SQL query
     * @throws PDOException
     */
    public function statements(string $query)
    {
        try {
            $this->pdo->exec($query);
        } catch (\Throwable $exception) {
            throw static::wrapException($exception, $query);
        }
    }

    /**
     * Executes statements from a file.
     *
     * @param string|resource $file A file path or a read resource. If a resource is given, it will be read to the end
     *     end closed.
     * @throws PDOException
     * @throws InvalidArgumentException
     * @throws FileException
     */
    public function import($file)
    {
        $resource = $this->makeReadResource($file);

        // Maybe it will read and execute the resource statement by statement instead of reading all the resource at
        // once in the future, but it is not required yet.
        $sqlText = @stream_get_contents($resource);

        if ($sqlText === false) {
            $errorInfo = error_get_last();
            throw new FileException(sprintf(
                'Failed to read from the resource%s',
                $errorInfo ? ': '.$errorInfo['message'] : ''
            ));
        }

        @fclose($resource);
        $this->statements($sqlText);
    }

    /**
     * Returns the used PDO instance.
     *
     * @return \PDO You MUST NOT MODIFY it
     */
    public function getPDO(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Executes a single SQL statement and returns the corresponding PDO statement.
     *
     * @param string $query Full SQL query
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return \PDOStatement
     * @throws InvalidArgumentException
     * @throws BasePDOException
     */
    protected function executeStatement(string $query, array $values = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $values);
        $statement->execute();
        return $statement;
    }

    /**
     * Binds parameters to a PDO statement.
     *
     * @param \PDOStatement $statement PDO statement
     * @param array $values Parameters. The indexes are the names or numbers of the values.
     * @throws InvalidArgumentException
     * @throws BasePDOException
     */
    protected function bindValues(\PDOStatement $statement, array $values)
    {
        $number = 1;

        foreach ($values as $name => $value) {
            $this->bindValue($statement, is_string($name) ? $name : $number, $value);
            $number += 1;
        }
    }

    /**
     * Binds a value to a PDO statement.
     *
     * @param \PDOStatement $statement PDO statement
     * @param string|int $name Value placeholder name or index (if the placeholder is not named)
     * @param string|int|float|boolean|null $value Value to bind
     * @throws InvalidArgumentException
     * @throws BasePDOException
     */
    protected function bindValue(\PDOStatement $statement, $name, $value)
    {
        if ($value !== null && !is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                'Bound value %s expected to be scalar or null, a %s given',
                is_int($name) ? '#'.$name : '`'.$name.'`',
                gettype($value)
            ));
        }

        if ($value === null) {
            $type = \PDO::PARAM_NULL;
        } elseif (is_bool($value)) {
            $type = \PDO::PARAM_BOOL;
        } elseif (is_integer($value)) {
            $type = \PDO::PARAM_INT;
        } else {
            $type = \PDO::PARAM_STR;
        }

        $statement->bindValue($name, $value, $type);
    }

    /**
     * Makes a resource for reading data.
     *
     * @param string|resource $source A file path or a read resource
     * @return resource
     * @throws FileException
     * @throws InvalidArgumentException
     */
    protected function makeReadResource($source)
    {
        if (is_resource($source)) {
            return $source;
        }

        if (is_string($source)) {
            $resource = @fopen($source, 'r');

            if ($resource) {
                return $resource;
            }

            $errorInfo = error_get_last();
            throw new FileException(sprintf(
                'Unable to open the file `%s` for reading%s',
                $source,
                $errorInfo ? ': '.$errorInfo['message'] : ''
            ));
        }

        throw new InvalidArgumentException(sprintf(
            'The given source expected to be a file path of a resource, a %s given',
            is_object($source) ? get_class($source).' instance' : gettype($source)
        ));
    }

    /**
     * Creates a library exception from a PHP exception if possible.
     *
     * @param \Throwable $exception
     * @param string|null $query SQL query which caused the error (if caused by a query)
     * @param array|null $values Bound values (if caused by a query)
     * @return IException|\Throwable
     */
    protected static function wrapException(
        \Throwable $exception,
        string $query = null,
        array $values = null
    ): \Throwable {
        if ($exception instanceof BasePDOException) {
            return PDOException::wrapBaseException($exception, $query, $values);
        }

        return $exception;
    }
}
