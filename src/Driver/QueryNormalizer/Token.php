<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

final readonly class Token
{
    public function __construct(
        private TokenType $type,
        private string $value,
    ) {
    }

    public function getType(): TokenType
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isNamed(): bool
    {
        return TokenType::NAMED === $this->type;
    }

    public function isPositional(): bool
    {
        return TokenType::POSITIONAL === $this->type;
    }

    public function getName(): string
    {
        return ltrim($this->value, ':');
    }
}
