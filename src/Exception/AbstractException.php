<?php

declare(strict_types=1);

namespace Organization\Package\Exception;

use RuntimeException;
use Throwable;

abstract class AbstractException extends RuntimeException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
