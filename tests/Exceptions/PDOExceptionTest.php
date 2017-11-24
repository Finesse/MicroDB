<?php

namespace Finesse\MicroDB\Tests\Exceptions;

use Finesse\MicroDB\Exceptions\PDOException;
use Finesse\MicroDB\Tests\TestCase;
use PDOException as BasePDOException;

/**
 * Tests the PDOException class
 *
 * @author Surgie
 */
class PDOExceptionTest extends TestCase
{
    /**
     * Tests that an exception message and other details are composed correctly
     */
    public function testContent()
    {
        $exception = new PDOException(
            'Something has happened',
            'IO012',
            new BasePDOException(),
            'INCORRECT SQL FOR ERROR',
            [
                true,
                false,
                null,
                1234,
                'short string',
                "Lorem Ipsum is simply dummy text of the printing and typesetting industry."
                    . " Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.",
                new PDOException(),
                fopen('php://temp', 'r'),
                [1, 2, 3]
            ]
        );
        $this->assertEquals(
            'Something has happened; SQL query: (INCORRECT SQL FOR ERROR); bound values: [true, false, null, 1234, '
                . '"short string", "Lorem Ipsum is simply dummy text of the printing and typesetting industry.'
                . ' Lorem Ipsum has been t...", a Finesse\\MicroDB\\Exceptions\\PDOException instance, a resource,'
                . ' [1, 2, 3]]',
            $exception->getMessage()
        );
        $this->assertEquals('IO012', $exception->getCode());
        $this->assertEquals('INCORRECT SQL FOR ERROR', $exception->getQuery());
        $this->assertCount(9, $exception->getValues());
        $this->assertInstanceOf(BasePDOException::class, $exception->getPrevious());

        // Values are an associative array
        $exception = new PDOException('Test', null, null, 'SQL', [
            'one',
            'key' => 'two',
            ['a' => 'A', 'b' => 'B'],
            423 => 'four'
        ]);
        $this->assertEquals(
            'Test; SQL query: (SQL); bound values:'
                . ' [0 => "one", "key" => "two", 1 => ["a" => "A", "b" => "B"], 423 => "four"]',
            $exception->getMessage()
        );
    }

    /**
     * Tests the `wrapBaseException` method
     */
    public function testWrapBaseException()
    {
        $baseException = new BasePDOException('Everything is broken', 7392);
        $baseException->errorInfo = 'some info';

        $exception = PDOException::wrapBaseException($baseException, 'DROP EVERYTHING', [':foo' => 'bar']);
        $this->assertEquals(
            'Everything is broken; SQL query: (DROP EVERYTHING); bound values: [":foo" => "bar"]',
            $exception->getMessage()
        );
        $this->assertEquals(7392, $exception->getCode());
        $this->assertEquals($baseException, $exception->getPrevious());
        $this->assertEquals('some info', $exception->errorInfo);
    }
}
