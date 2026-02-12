<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\DataTransformer;

final readonly class FirebirdDataTransformer implements DataTransformerInterface
{
    private const string SOURCE_ENCODING = 'Windows-1252';

    private const string TARGET_ENCODING = 'UTF-8';

    public function encode(mixed $value): mixed
    {
        if (\is_array($value)) {
            return $this->encodeArray($value);
        }

        if (false === \is_string($value)) {
            return $value;
        }

        return mb_convert_encoding($value, self::SOURCE_ENCODING, self::TARGET_ENCODING);
    }

    public function decode(mixed $value): mixed
    {
        if (\is_array($value)) {
            return $this->decodeArray($value);
        }

        if (false === \is_string($value)) {
            return $value;
        }

        return mb_convert_encoding($value, self::TARGET_ENCODING, self::SOURCE_ENCODING);
    }

    /**
     * @param array<string, scalar|null> $array
     *
     * @return array<string, scalar|null>
     */
    private function encodeArray(array $array): array
    {
        $encoded = [];

        foreach ($array as $key => $item) {
            $encoded[$key] = $this->encode($item);
        }

        return $encoded;
    }

    /**
     * @param array<string, scalar|null> $array
     *
     * @return array<string, scalar|null>
     */
    private function decodeArray(array $array): array
    {
        $decoded = [];

        foreach ($array as $key => $item) {
            $decoded[$key] = $this->decode($item);
        }

        return $decoded;
    }
}
