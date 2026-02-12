<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Driver\DataTransformer\FirebirdDataTransformer;
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
use Psr\EventDispatcher\EventDispatcherInterface;

final class FirebirdDriver implements DriverInterface
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?DataTransformerInterface $dataTransformer = null,
        private readonly ?QueryNormalizerInterface $queryNormalizer = null,
    ) {
    }

    public function connect(string $dsn, ?string $username = null, ?string $password = null): void
    {
        try {
            $this->connection = new PDO($dsn, $username, $password);

            $this->eventDispatcher?->dispatch(new ConnectionEstablishedEvent($dsn));
        } catch (PDOException $exception) {
            throw new ConnectionException('Could not connect to the Firebird database', $exception);
        }
    }

    public function execute(string $query, array $parameters = []): Result
    {
        if (null === $this->connection) {
            throw new QueryException('No active database connection');
        }

        try {
            $startTime = microtime(true);

            $transformer = $this->dataTransformer ?? new FirebirdDataTransformer();
            $normalizer = $this->queryNormalizer ?? new DefaultQueryNormalizer(new RegexQueryTokenizer());

            $normalized = $normalizer->normalize($query, $parameters);
            $normalizedQuery = $normalized->getQuery();
            $normalizedParameters = $normalized->getParameters();

            $statement = $this->connection->prepare($normalizedQuery);

            foreach ($normalizedParameters as $key => $value) {
                $type = PDO::PARAM_STR;
                $value = $transformer->encode($value);

                if (\is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (null === $value) {
                    $type = PDO::PARAM_NULL;
                } elseif (\is_bool($value)) {
                    $type = PDO::PARAM_INT;
                }

                $statement->bindValue($key, $value, $type);
            }

            $statement->execute();

            $executionTime = microtime(true) - $startTime;

            $this->eventDispatcher?->dispatch(new QueryExecutedEvent($normalizedQuery, $normalizedParameters, $executionTime));

            return new Result($statement, $transformer);
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
