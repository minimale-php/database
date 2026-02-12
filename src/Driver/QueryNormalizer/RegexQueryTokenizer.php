<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

final class RegexQueryTokenizer implements QueryTokenizerInterface
{
    private const string NAMED_PLACEHOLDER_PATTERN = '/^:[a-zA-Z_]\w*$/';

    /**
     * Matches (in priority order): single-quoted strings with '' and \' escapes,
     * double-quoted strings with "" and \" escapes, named placeholders (not preceded
     * by another colon, to skip PostgreSQL casts), positional ?, regular text, or
     * any remaining single character as a fallback. Unicode-aware via /u flag.
     */
    private const string TOKEN_PATTERN = "/'(?:[^'\\\\]|\\\\.|\'{2})*'|\"(?:[^\"\\\\]|\\\\.|\"{2})*\"|(?<!:):[a-zA-Z_]\w*|\?|[^'\"?:]+|./us";

    public function tokenize(string $query): array
    {
        preg_match_all(self::TOKEN_PATTERN, $query, $matches);

        return array_map($this->createToken(...), $matches[0]);
    }

    private function createToken(string $value): Token
    {
        return new Token($this->resolveType($value), $value);
    }

    private function resolveType(string $value): TokenType
    {
        if ('?' === $value) {
            return TokenType::POSITIONAL;
        }

        if (1 === preg_match(self::NAMED_PLACEHOLDER_PATTERN, $value)) {
            return TokenType::NAMED;
        }

        return TokenType::TEXT;
    }
}
