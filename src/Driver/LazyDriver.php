<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Event\LazyConnectionRequestedEvent;
use Minimale\Database\Exception\ConnectionException;
use Minimale\Database\Result;
use Psr\EventDispatcher\EventDispatcherInterface;

final class LazyDriver implements DriverInterface
{
    private bool $connected = false;

    public function __construct(
        private readonly DriverInterface $driver,
        private readonly string $alias,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function connect(string $dsn, ?string $username = null, ?string $password = null): void
    {
        if ($this->connected) {
            return;
        }

        $this->driver->connect($dsn, $username, $password);
        $this->connected = true;
    }

    /**
     * @param array<scalar|array<scalar>|null> $parameters
     */
    public function execute(string $query, array $parameters = []): Result
    {
        $this->ensureConnected();

        return $this->driver->execute($query, $parameters);
    }

    public function disconnect(): void
    {
        if ($this->connected) {
            $this->driver->disconnect();
            $this->connected = false;
        }
    }

    public function beginTransaction(): void
    {
        $this->ensureConnected();
        $this->driver->beginTransaction();
    }

    public function commit(): void
    {
        $this->ensureConnected();
        $this->driver->commit();
    }

    public function rollback(): void
    {
        $this->ensureConnected();
        $this->driver->rollback();
    }

    /**
     * @throws ConnectionException if the connection could not be established after dispatching the event
     */
    private function ensureConnected(): void
    {
        if ($this->connected) {
            return;
        }

        $this->eventDispatcher->dispatch(new LazyConnectionRequestedEvent($this->alias, $this));

        $this->assertConnected();
    }

    /**
     * @throws ConnectionException if the driver is not connected
     */
    private function assertConnected(): void
    {
        if (!$this->connected) {
            throw new ConnectionException(
                \sprintf('Lazy connection for driver "%s" could not be established', $this->alias),
            );
        }
    }
}
