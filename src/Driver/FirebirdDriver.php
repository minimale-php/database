<?php

declare(strict_types=1);

namespace Minimale\Database\Driver;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Driver\DataTransformer\FirebirdDataTransformer;
use Override;
use PDO;
use PDOStatement;

final class FirebirdDriver extends AbstractDriver
{
    #[Override]
    protected function getDriverName(): string
    {
        return 'Firebird';
    }

    #[Override]
    protected function createDefaultDataTransformer(): DataTransformerInterface
    {
        return new FirebirdDataTransformer();
    }

    #[Override]
    protected function bindAndExecute(PDOStatement $statement, array $parameters, DataTransformerInterface $transformer): void
    {
        foreach ($parameters as $key => $value) {
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
    }
}
