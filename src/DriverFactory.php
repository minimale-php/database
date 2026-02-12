<?php

declare(strict_types=1);

namespace Minimale\Database;

use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\Driver\FirebirdDriver;
use Minimale\Database\Driver\SQLiteDriver;
use Minimale\Database\Exception\DriverException;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class DriverFactory
{
    private const int EXPLODE_LIMIT = 2;

    /**
     * @param array{dsn?: string|null, username?: string|null, password?: string|null} $config
     */
    public static function create(array $config, ?EventDispatcherInterface $eventDispatcher = null): DriverInterface
    {
        if (false === isset($config['dsn'])) {
            throw new DriverException('DSN is required in configuration');
        }

        $dsn = $config['dsn'];
        $driverType = self::inferDriverType($dsn);

        return match ($driverType) {
            'sqlite' => new SQLiteDriver($eventDispatcher),
            'firebird' => new FirebirdDriver($eventDispatcher),
            default => throw new DriverException(\sprintf('Unsupported driver type: %s', $driverType)),
        };
    }

    private static function inferDriverType(string $dsn): string
    {
        $parts = explode(':', $dsn, self::EXPLODE_LIMIT);

        if (\count($parts) < 2) {
            throw new DriverException(\sprintf('Invalid DSN format: %s', $dsn));
        }

        return strtolower($parts[0]);
    }
}
