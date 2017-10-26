<?php

namespace Finesse\MicroDB;

use Finesse\MicroDB\Exceptions\InvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException;
use InvalidArgumentException as BaseInvalidArgumentException;
use PDOException as BasePDOException;

/**
 * Wraps a PDO object for more convenient usage.
 *
 * @author Surgie
 */
class DB
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
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
    }

    /**
     * Creates the self instance.
     *
     * @param array ...$pdoArgs Arguments for the PDO constructor
     * @return static
     * @throws PDOException
     * @see http://php.net/manual/en/pdo.construct.php Arguments reference
     */
    public static function create(...$pdoArgs): self
    {
        try {
            return new static(new \PDO(...$pdoArgs));
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a select query and returns the query results.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function select(string $query, array $parameters = []): array
    {
        try {
            return $this->executeQuery($query, $parameters)->fetchAll();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a select query and returns the first query result.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function selectFirst(string $query, array $parameters = [])
    {
        try {
            $row = $this->executeQuery($query, $parameters)->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a insert query and returns the number of inserted rows.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function insert(string $query, array $parameters = []): int
    {
        try {
            return $this->executeQuery($query, $parameters)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a insert query and returns the identifier of the last inserted row.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @param string|null $sequence Name of the sequence object from which the ID should be returned
     * @return int|string
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function insertGetId(string $query, array $parameters = [], string $sequence = null)
    {
        try {
            $this->executeQuery($query, $parameters);
            $id = $this->pdo->lastInsertId($sequence);
            return is_numeric($id) ? (int)$id : $id;
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a update query.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int The number of updated rows
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function update(string $query, array $parameters = []): int
    {
        try {
            return $this->executeQuery($query, $parameters)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a delete query.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int The number of deleted rows
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function delete(string $query, array $parameters = []): int
    {
        try {
            return $this->executeQuery($query, $parameters)->rowCount();
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
    }

    /**
     * Performs a general query.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function statement(string $query, array $parameters = [])
    {
        try {
            $this->executeQuery($query, $parameters);
        } catch (\Throwable $exception) {
            throw static::wrapException($exception);
        }
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
     * Executes a SQL query and returns the corresponding PDO statement.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return \PDOStatement
     * @throws InvalidArgumentException
     * @throws BasePDOException
     */
    protected function executeQuery(string $query, array $parameters = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $parameters);
        $statement->execute();
        return $statement;
    }

    /**
     * Binds parameters to a PDO statement.
     *
     * @param \PDOStatement $statement PDO statement
     * @param array $parameters Parameters. The indexes are the names or numbers of the parameters.
     * @throws InvalidArgumentException
     * @throws BasePDOException
     */
    protected function bindValues(\PDOStatement $statement, array $parameters)
    {
        $number = 1;

        foreach ($parameters as $name => $value) {
            $this->bindValue($statement, is_string($name) ? $name : $number, $value);
            $number += 1;
        }
    }

    /**
     * Binds a value to a PDO statement.
     *
     * @param \PDOStatement $statement PDO statement
     * @param string|int $parameter Value placeholder name or index (if the placeholder is not named)
     * @param string|int|float|boolean|null $value Value to bind
     * @throws BaseInvalidArgumentException
     * @throws BasePDOException
     */
    protected function bindValue(\PDOStatement $statement, $parameter, $value)
    {
        if ($value !== null && !is_scalar($value)) {
            throw new BaseInvalidArgumentException(
                'Argument $value expected to be scalar or null, a '.gettype($value).' given'
            );
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

        $statement->bindValue($parameter, $value, $type);
    }

    /**
     * Creates a library exception from a PHP exception if possible.
     *
     * @param \Throwable $exception
     * @return IException|\Throwable
     */
    protected static function wrapException(\Throwable $exception): \Throwable
    {
        if ($exception instanceof IException) {
            return $exception;
        }
        if ($exception instanceof BasePDOException) {
            $newException = new PDOException($exception->getMessage(), $exception->getCode(), $exception);
            $newException->errorInfo = $exception->errorInfo;
            return $newException;
        }
        if ($exception instanceof BaseInvalidArgumentException) {
            return new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $exception;
    }
}
