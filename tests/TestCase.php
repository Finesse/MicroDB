<?php

namespace Finesse\MicroDB\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case class for other rests
 *
 * @author Surgie
 */
class TestCase extends BaseTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
