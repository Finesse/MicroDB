<?php

namespace Finesse\MicroDB\Exceptions;

use Finesse\MicroDB\IException;
use Throwable;

/**
 * {@inheritDoc}
 *
 * @author Surgie
 */
class PDOException extends \PDOException implements IException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        // The constructor doesn't except string as a $code value so we have to set it manually
        $this->code = $code;
    }
}
