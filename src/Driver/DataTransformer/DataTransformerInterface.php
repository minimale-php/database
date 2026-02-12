<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\DataTransformer;

interface DataTransformerInterface
{
    /**
     * @template T of array<string, scalar|null>|scalar|null
     *
     * @param T $value
     *
     * @return (T is array ? array<string, scalar|null> : scalar|null)
     */
    public function encode(mixed $value): mixed;

    /**
     * @template T of array<string, scalar|null>|scalar|null
     *
     * @param T $value
     *
     * @return (T is array ? array<string, scalar|null> : scalar|null)
     */
    public function decode(mixed $value): mixed;
}
