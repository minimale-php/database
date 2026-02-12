<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

use Minimale\Database\Exception\QueryNormalizerException;

interface QueryNormalizerInterface
{
    /**
     * @param array<scalar|array<scalar>|null> $parameters
     *
     * @throws QueryNormalizerException if a parameter is missing or if an array parameter is empty
     */
    public function normalize(string $query, array $parameters): NormalizedQuery;
}
