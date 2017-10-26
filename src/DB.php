<?php

namespace Finesse\MicroDB;

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
     * @throws \PDOException
     * @see http://php.net/manual/en/pdo.construct.php Arguments reference
     */
    public static function create(...$pdoArgs): self
    {
        return new static(new \PDO(...$pdoArgs));
    }

    /**
     * Performs a select query and returns the query results.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function select(string $query, array $parameters = []): array
    {
        return $this->executeQuery($query, $parameters)->fetchAll();
    }

    /**
     * Performs a select query and returns the first query result.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function selectFirst(string $query, array $parameters = [])
    {
        $row = $this->executeQuery($query, $parameters)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Performs a insert query and returns the number of inserted rows.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function insert(string $query, array $parameters = []): int
    {
        return $this->executeQuery($query, $parameters)->rowCount();
    }

    /**
     * Performs a insert query and returns the identifier of the last inserted row.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @param string|null $sequence Name of the sequence object from which the ID should be returned
     * @return int|string
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function insertGetId(string $query, array $parameters = [], string $sequence = null)
    {
        $this->executeQuery($query, $parameters);
        $id = $this->pdo->lastInsertId($sequence);
        return is_numeric($id) ? (int)$id : $id;
    }

    /**
     * Performs a update query.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int The number of updated rows
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function update(string $query, array $parameters = []): int
    {
        return $this->executeQuery($query, $parameters)->rowCount();
    }

    /**
     * Performs a delete query.
     *
     * @param string $query Full SQL query
     * @param array $parameters Parameters to bind. The indexes are the names or numbers of the parameters.
     * @return int The number of deleted rows
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function delete(string $query, array $parameters = []): int
    {
        return $this->executeQuery($query, $parameters)->rowCount();
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
     * @throws \InvalidArgumentException
     * @throws \PDOException
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
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    protected function bindValues(\PDOStatement $statement, array $parameters)
    {
        foreach ($parameters as $name => $value) {
            $this->bindValue($statement, is_string($name) ? $name : $name + 1, $value);
        }
    }

    /**
     * Binds a value to a PDO statement.
     *
     * @param \PDOStatement $statement PDO statement
     * @param string|int $parameter Value placeholder name or index (if the placeholder is not named)
     * @param string|int|float|boolean|null $value Value to bind
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    protected function bindValue(\PDOStatement $statement, $parameter, $value)
    {
        if ($value !== null && !is_scalar($value)) {
            throw new \InvalidArgumentException(
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
}
