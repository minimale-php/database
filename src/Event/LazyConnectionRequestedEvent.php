<?php

declare(strict_types=1);

namespace Minimale\Database\Event;

use Minimale\Database\Driver\LazyDriver;

final readonly class LazyConnectionRequestedEvent
{
    public function __construct(
        private string $alias,
        private LazyDriver $driver,
    ) {
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDriver(): LazyDriver
    {
        return $this->driver;
    }
}
