<?php

declare(strict_types=1);

namespace Minimale\Database\Exception;

use RuntimeException;
use Throwable;

abstract class AbstractException extends RuntimeException
{
    private const int DEFAULT_CODE = 50876;

    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, self::DEFAULT_CODE, $previous);
    }
}
