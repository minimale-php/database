<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Driver\DataTransformer\PassthroughDataTransformer;
use Override;
use PDOStatement;

final class SQLiteDriver extends AbstractDriver
{
    #[Override]
    protected function getDriverName(): string
    {
        return 'SQLite';
    }

    #[Override]
    protected function createDefaultDataTransformer(): DataTransformerInterface
    {
        return new PassthroughDataTransformer();
    }

    #[Override]
    protected function bindAndExecute(PDOStatement $statement, array $parameters, DataTransformerInterface $transformer): void
    {
        $transformed = [];

        foreach ($parameters as $key => $value) {
            $transformed[$key] = $transformer->encode($value);
        }

        $statement->execute($transformed);
    }
}
