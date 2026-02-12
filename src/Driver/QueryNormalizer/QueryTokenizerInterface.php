<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

interface QueryTokenizerInterface
{
    /**
     * @return Token[]
     */
    public function tokenize(string $query): array;
}
