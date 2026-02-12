<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Exception\ConnectionException;
use Minimale\Database\Exception\QueryException;
use Minimale\Database\Exception\TransactionException;
use Minimale\Database\Result;

interface DriverInterface
{
    /**
     * @throws ConnectionException if the connection could not be established
     *
     * @emits ConnectionEstablishedEvent when a connection is successfully established
     */
    public function connect(string $dsn, ?string $username = null, ?string $password = null): void;

    /**
     * @param array<scalar|array<scalar>|null> $parameters
     *
     * @throws QueryException if the query execution fails
     *
     * @emits QueryExecutedEvent when a query is successfully executed
     */
    public function execute(string $query, array $parameters = []): Result;

    /**
     * @emits ConnectionClosedEvent when the connection is successfully closed
     */
    public function disconnect(): void;

    /**
     * @throws TransactionException if a transaction is already active or if the transaction could not be started
     *
     * @emits TransactionBeganEvent when a transaction is successfully started
     */
    public function beginTransaction(): void;

    /**
     * @throws TransactionException if no transaction is active or if the transaction could not be committed
     *
     * @emits TransactionCommittedEvent when a transaction is successfully committed
     */
    public function commit(): void;

    /**
     * @throws TransactionException if no transaction is active or if the transaction could not be rolled back
     *
     * @emits TransactionRolledBackEvent when a transaction is successfully rolled back
     */
    public function rollback(): void;
}
