<?php

declare(strict_types=1);

namespace Minimale\Database\Event;

final readonly class ConnectionEstablishedEvent
{
    public function __construct(
        private string $dsn,
    ) {
    }

    public function getDSN(): string
    {
        return $this->dsn;
    }
}
