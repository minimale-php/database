<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\DataTransformer;

final readonly class PassthroughDataTransformer implements DataTransformerInterface
{
    public function encode(mixed $value): mixed
    {
        return $value;
    }

    public function decode(mixed $value): mixed
    {
        return $value;
    }
}
