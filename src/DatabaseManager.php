<?php

declare(strict_types=1);

namespace Minimale\Database;

use Minimale\Database\Driver\DriverInterface;

final readonly class DatabaseManager
{
    public function __construct(
        private DriverInterface $driver,
    ) {
    }

    /**
     * @param array<scalar|array<scalar>|null> $parameters
     */
    public function execute(string $query, array $parameters = []): Result
    {
        return $this->driver->execute($query, $parameters);
    }

    public function beginTransaction(): void
    {
        $this->driver->beginTransaction();
    }

    public function commit(): void
    {
        $this->driver->commit();
    }

    public function rollback(): void
    {
        $this->driver->rollback();
    }
}
