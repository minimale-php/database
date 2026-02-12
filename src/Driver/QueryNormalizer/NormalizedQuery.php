<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

final readonly class NormalizedQuery
{
    /**
     * @param array<string, scalar|null> $parameters
     */
    public function __construct(
        private string $query,
        private array $parameters,
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
}
