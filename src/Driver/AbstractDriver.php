<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Driver\QueryNormalizer\DefaultQueryNormalizer;
use Minimale\Database\Driver\QueryNormalizer\QueryNormalizerInterface;
use Minimale\Database\Driver\QueryNormalizer\RegexQueryTokenizer;
use Minimale\Database\Event\ConnectionClosedEvent;
use Minimale\Database\Event\ConnectionEstablishedEvent;
use Minimale\Database\Event\QueryExecutedEvent;
use Minimale\Database\Event\TransactionBeganEvent;
use Minimale\Database\Event\TransactionCommittedEvent;
use Minimale\Database\Event\TransactionRolledBackEvent;
use Minimale\Database\Exception\ConnectionException;
use Minimale\Database\Exception\QueryException;
use Minimale\Database\Exception\TransactionException;
use Minimale\Database\Result;
use PDO;
use PDOException;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractDriver implements DriverInterface
{
    private ?PDO $connection = null;

    private readonly DataTransformerInterface $dataTransformer;

    private readonly QueryNormalizerInterface $queryNormalizer;

    public function __construct(
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        ?DataTransformerInterface $dataTransformer = null,
        ?QueryNormalizerInterface $queryNormalizer = null,
    ) {
        $this->dataTransformer = $dataTransformer ?? $this->createDefaultDataTransformer();
        $this->queryNormalizer = $queryNormalizer ?? new DefaultQueryNormalizer(new RegexQueryTokenizer());
    }

    abstract protected function getDriverName(): string;

    abstract protected function createDefaultDataTransformer(): DataTransformerInterface;

    /**
     * @param array<string, scalar|null> $parameters
     */
    abstract protected function bindAndExecute(PDOStatement $statement, array $parameters, DataTransformerInterface $transformer): void;

    public function connect(string $dsn, ?string $username = null, ?string $password = null): void
    {
        try {
            $this->connection = new PDO($dsn, $username, $password);

            $this->eventDispatcher?->dispatch(new ConnectionEstablishedEvent($dsn));
        } catch (PDOException $exception) {
            throw new ConnectionException(\sprintf('Could not connect to the %s database', $this->getDriverName()), $exception);
        }
    }

    public function execute(string $query, array $parameters = []): Result
    {
        if (null === $this->connection) {
            throw new QueryException('No active database connection');
        }

        try {
            $startTime = microtime(true);

            $normalized = $this->queryNormalizer->normalize($query, $parameters);
            $normalizedQuery = $normalized->getQuery();
            $normalizedParameters = $normalized->getParameters();

            $statement = $this->connection->prepare($normalizedQuery);

            $this->bindAndExecute($statement, $normalizedParameters, $this->dataTransformer);

            $executionTime = microtime(true) - $startTime;

            $this->eventDispatcher?->dispatch(new QueryExecutedEvent($normalizedQuery, $normalizedParameters, $executionTime));

            return new Result($statement, $this->dataTransformer);
        } catch (PDOException $exception) {
            throw new QueryException('Could not execute query', $exception);
        }
    }

    public function disconnect(): void
    {
        if (null !== $this->connection) {
            $this->connection = null;
            $this->eventDispatcher?->dispatch(new ConnectionClosedEvent());
        }
    }

    public function beginTransaction(): void
    {
        if (null === $this->connection) {
            throw new TransactionException('No active database connection');
        }

        try {
            $this->connection->beginTransaction();
            $this->eventDispatcher?->dispatch(new TransactionBeganEvent());
        } catch (PDOException $exception) {
            throw new TransactionException('Could not begin transaction', $exception);
        }
    }

    public function commit(): void
    {
        if (null === $this->connection) {
            throw new TransactionException('No active database connection');
        }

        try {
            $this->connection->commit();
            $this->eventDispatcher?->dispatch(new TransactionCommittedEvent());
        } catch (PDOException $exception) {
            throw new TransactionException('Could not commit transaction', $exception);
        }
    }

    public function rollback(): void
    {
        if (null === $this->connection) {
            throw new TransactionException('No active database connection');
        }

        try {
            $this->connection->rollBack();
            $this->eventDispatcher?->dispatch(new TransactionRolledBackEvent());
        } catch (PDOException $exception) {
            throw new TransactionException('Could not roll back transaction', $exception);
        }
    }
}
