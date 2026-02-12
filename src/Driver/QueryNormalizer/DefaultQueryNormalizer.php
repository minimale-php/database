<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

use Minimale\Database\Exception\QueryNormalizerException;

final readonly class DefaultQueryNormalizer implements QueryNormalizerInterface
{
    public function __construct(
        private QueryTokenizerInterface $tokenizer,
    ) {
    }

    public function normalize(string $query, array $parameters): NormalizedQuery
    {
        $tokens = $this->tokenizer->tokenize($query);
        $placeholderCounts = $this->countPlaceholderOccurrences($tokens);

        $normalizedQuery = '';
        $normalizedParams = [];
        $positionalIndex = 0;
        $positionalCounter = 0;
        $namedOccurrences = [];
        $namedArrayOffsets = [];

        foreach ($tokens as $token) {
            if ($token->isPositional()) {
                if (false === \array_key_exists($positionalIndex, $parameters)) {
                    throw new QueryNormalizerException(\sprintf('Could not find a positional parameter at index %d', $positionalIndex));
                }

                $value = $parameters[$positionalIndex++];

                if (\is_array($value)) {
                    if ([] === $value) {
                        throw new QueryNormalizerException(\sprintf('Could not expand an empty array for positional parameter at index %d', $positionalIndex - 1));
                    }

                    [$fragment, $params, $positionalCounter] = $this->expandArray('param', $value, $positionalCounter);

                    $normalizedQuery .= $fragment;
                    $normalizedParams = array_merge($normalizedParams, $params);
                } else {
                    ++$positionalCounter;
                    $normalizedQuery .= ":param_{$positionalCounter}";
                    $normalizedParams["param_{$positionalCounter}"] = $value;
                }

                continue;
            }

            if ($token->isNamed()) {
                $name = $token->getName();

                if (false === \array_key_exists($name, $parameters)) {
                    throw new QueryNormalizerException(\sprintf('Could not find a named parameter "%s"', $name));
                }

                $value = $parameters[$name];
                $occurrence = $namedOccurrences[$name] = ($namedOccurrences[$name] ?? 0) + 1;

                if (\is_array($value)) {
                    if ([] === $value) {
                        throw new QueryNormalizerException(\sprintf('Could not expand an empty array for named parameter "%s"', $name));
                    }

                    $offset = $namedArrayOffsets[$name] ?? 0;

                    [$fragment, $params, $namedArrayOffsets[$name]] = $this->expandArray($name, $value, $offset);

                    $normalizedQuery .= $fragment;
                    $normalizedParams = array_merge($normalizedParams, $params);
                } elseif ($placeholderCounts[$name] > 1) {
                    $normalizedQuery .= ":{$name}_{$occurrence}";
                    $normalizedParams["{$name}_{$occurrence}"] = $value;
                } else {
                    $normalizedQuery .= $token->getValue();
                    $normalizedParams[$name] = $value;
                }

                continue;
            }

            $normalizedQuery .= $token->getValue();
        }

        return new NormalizedQuery($normalizedQuery, $normalizedParams);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array{string, array<string, scalar>, int}
     */
    private function expandArray(string $prefix, array $values, int $offset): array
    {
        $fragments = [];
        $params = [];

        foreach ($values as $value) {
            if (false === \is_scalar($value)) {
                throw new QueryNormalizerException('Only scalar values are allowed in arrays');
            }

            ++$offset;
            $fragments[] = ":{$prefix}_{$offset}";
            $params["{$prefix}_{$offset}"] = $value;
        }

        return [implode(', ', $fragments), $params, $offset];
    }

    /**
     * @param Token[] $tokens
     *
     * @return array<string, int>
     */
    private function countPlaceholderOccurrences(array $tokens): array
    {
        $counts = [];

        foreach ($tokens as $token) {
            if ($token->isNamed()) {
                $name = $token->getName();
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
