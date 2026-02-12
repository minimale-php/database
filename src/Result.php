<?php

declare(strict_types=1);

namespace Minimale\Database;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Exception\ResultException;
use PDO;
use PDOException;
use PDOStatement;

final readonly class Result
{
    public function __construct(
        private PDOStatement $statement,
        private ?DataTransformerInterface $transformer = null,
    ) {
    }

    /**
     * @return array<string, scalar|null>|null
     */
    public function fetch(): ?array
    {
        try {
            /** @var array<string, scalar|null>|false $row */
            $row = $this->statement->fetch(PDO::FETCH_ASSOC);

            if (false === \is_array($row)) {
                return null;
            }

            if (null === $this->transformer) {
                return $row;
            }

            return $this->transformer->decode($row);
        } catch (PDOException $exception) {
            throw new ResultException('Could not fetch row', $exception);
        }
    }

    /**
     * @return scalar|null
     */
    public function fetchValue(): mixed
    {
        try {
            /** @var array<int, scalar|null>|false $row */
            $row = $this->statement->fetch(PDO::FETCH_NUM);

            $value = $row[0] ?? null;

            if (null === $value) {
                return null;
            }

            if (null === $this->transformer) {
                return $value;
            }

            return $this->transformer->decode($value);
        } catch (PDOException $exception) {
            throw new ResultException('Could not fetch value', $exception);
        }
    }

    /**
     * @return array<string, scalar|null>[]
     */
    public function fetchAll(): array
    {
        try {
            /** @var array<string, scalar|null>[] $rows */
            $rows = $this->statement->fetchAll(PDO::FETCH_ASSOC);

            if (null === $this->transformer) {
                return $rows;
            }

            return array_map($this->transformer->decode(...), $rows);
        } catch (PDOException $exception) {
            throw new ResultException('Could not fetch rows', $exception);
        }
    }

    public function rowCount(): int
    {
        try {
            return $this->statement->rowCount();
        } catch (PDOException $exception) {
            throw new ResultException('Could not get the row count', $exception);
        }
    }
}
