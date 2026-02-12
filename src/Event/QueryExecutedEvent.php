<?php

declare(strict_types=1);

namespace Minimale\Database\Event;

final readonly class QueryExecutedEvent
{
    /**
     * @param array<string, scalar|null> $parameters
     */
    public function __construct(
        private string $query,
        private array $parameters,
        private float $executionTime,
    ) {
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
}
