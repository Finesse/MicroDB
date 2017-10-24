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
        try {
            $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        } catch (\PDOException $e) {}
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
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $parameters);
        $statement->execute();
        return $statement->fetchAll();
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
    public function selectOne(string $query, array $parameters = [])
    {
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $parameters);
        $statement->execute();
        $row = $statement->fetch();
        return $row === false ? null : $row;
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

        if (is_null($value)) {
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
