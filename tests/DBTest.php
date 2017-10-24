<?php

namespace Finesse\MicroDB\Tests;

use Finesse\MicroDB\DB;
use Mockery;
use PDO;

/**
 * Tests the DB class.
 *
 * @author Surgie
 */
class DBTest extends TestCase
{
    /**
     * Tests that a given PDO object is not changed and that the inside PDO object can't be changed
     */
    public function testPdoImmutability()
    {
        /** @var \PDO|\Mockery\MockInterface $pdo */
        $pdo = Mockery::spy(PDO::class);
        new DB($pdo);
        $pdo->shouldNotHaveReceived('setAttribute');

        $pdo = new class extends PDO {
            protected $__attributes = [];
            public function __construct() {}
            public function getAttribute($attribute)
            {
                return $this->__attributes[$attribute] ?? null;
            }
            public function setAttribute($attribute, $value)
            {
                $this->__attributes[$attribute] = $value;
            }
        };
        $connection = new DB($pdo);
        $pdo1 = $connection->getPDO();
        $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $pdo2 = $connection->getPDO();
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo2->getAttribute(PDO::ATTR_ERRMODE));
    }
}
