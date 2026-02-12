<?php

declare(strict_types=1);

namespace Minimale\Database;

use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\Exception\RegistryException;

final class DriverRegistry
{
    /**
     * @var array<string, DriverInterface>
     */
    private array $drivers = [];

    public function add(string $alias, DriverInterface $driver): void
    {
        if (isset($this->drivers[$alias])) {
            throw new RegistryException(\sprintf('Driver with alias "%s" is already registered', $alias));
        }

        $this->drivers[$alias] = $driver;
    }

    public function get(string $alias): DriverInterface
    {
        if (false === $this->has($alias)) {
            throw new RegistryException(\sprintf('Driver with alias "%s" not found in registry', $alias));
        }

        return $this->drivers[$alias];
    }

    public function has(string $alias): bool
    {
        return isset($this->drivers[$alias]);
    }

    public function remove(string $alias): void
    {
        unset($this->drivers[$alias]);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_keys($this->drivers);
    }
}
