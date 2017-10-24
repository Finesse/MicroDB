<?php

namespace Finesse\MicroDB;

use PDO;

/**
 * Wraps a PDO object for more convenient usage.
 *
 * @author Surgie
 */
class DB
{
    /**
     * @var PDO The PDO instance. Throws exceptions on errors. Should not be given outside to prevent object mutating.
     */
    protected $pdo;

    /**
     * Connection constructor.
     *
     * @param \PDO $pdo A PDO instance to work with. It will not be changed anyhow.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = clone $pdo; // Cloning PDO doesn't establish a new connection
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Returns the used PDO instance.
     *
     * @return PDO You can change it, it will not affect the Connection instance.
     */
    public function getPDO(): PDO
    {
        return clone $this->pdo;
    }
}
