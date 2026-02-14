<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver\QueryNormalizer;

use Minimale\Database\Driver\QueryNormalizer\RegexQueryTokenizer;
use Minimale\Database\Driver\QueryNormalizer\TokenType;
use Minimale\Database\Tests\AbstractTestCase;
use Override;

final class RegexQueryTokenizerTest extends AbstractTestCase
{
    private RegexQueryTokenizer $tokenizer;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenizer = new RegexQueryTokenizer();
    }

    public function testTokenizeEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->tokenizer->tokenize('');

        self::assertSame([], $result);
    }

    public function testTokenizePlainTextReturnsOneTextToken(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users');

        self::assertCount(1, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame('SELECT * FROM users', $result[0]->getValue());
    }

    public function testTokenizePositionalPlaceholder(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = ?');

        self::assertCount(2, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame('SELECT * FROM users WHERE id = ', $result[0]->getValue());
        self::assertSame(TokenType::POSITIONAL, $result[1]->getType());
        self::assertSame('?', $result[1]->getValue());
    }

    public function testTokenizeNamedPlaceholder(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :id');

        self::assertCount(2, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame('SELECT * FROM users WHERE id = ', $result[0]->getValue());
        self::assertSame(TokenType::NAMED, $result[1]->getType());
        self::assertSame(':id', $result[1]->getValue());
    }

    public function testTokenizeMultiplePositionalPlaceholders(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = ? AND name = ?');

        $positionalTokens = array_filter($result, static fn ($token) => TokenType::POSITIONAL === $token->getType());

        self::assertCount(2, $positionalTokens);
    }

    public function testTokenizeMultipleNamedPlaceholders(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :id AND name = :name');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(2, $namedTokens);
        self::assertSame(':id', array_first($namedTokens)->getValue());
        self::assertSame(':name', array_values($namedTokens)[1]->getValue());
    }

    public function testTokenizeMixedPlaceholders(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :id AND age > ?');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());
        $positionalTokens = array_filter($result, static fn ($token) => TokenType::POSITIONAL === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertCount(1, $positionalTokens);
    }

    public function testTokenizeSingleQuotedStringAsText(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = 'John'");

        self::assertCount(2, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame(TokenType::TEXT, $result[1]->getType());
        self::assertSame("'John'", $result[1]->getValue());
    }

    public function testTokenizeDoubleQuotedStringAsText(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE name = "John"');

        self::assertCount(2, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame(TokenType::TEXT, $result[1]->getType());
        self::assertSame('"John"', $result[1]->getValue());
    }

    public function testTokenizePreservesPlaceholderInsideSingleQuotedString(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = ':not_a_param'");

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizePreservesQuestionMarkInsideSingleQuotedString(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = 'what?'");

        $positionalTokens = array_filter($result, static fn ($token) => TokenType::POSITIONAL === $token->getType());

        self::assertCount(0, $positionalTokens);
    }

    public function testTokenizePreservesPlaceholderInsideDoubleQuotedString(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE name = ":not_a_param"');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizeHandlesEscapedSingleQuote(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = 'O''Brien'");

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizeHandlesBackslashEscapedQuote(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = 'O\\'Brien'");

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizeHandlesPostgreSqlCast(): void
    {
        $result = $this->tokenizer->tokenize('SELECT id::text FROM users');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizeHandlesPostgreSqlCastWithNamedPlaceholder(): void
    {
        $result = $this->tokenizer->tokenize('SELECT id::text FROM users WHERE name = :name');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertSame(':name', array_first($namedTokens)->getValue());
    }

    public function testTokenizeNamedPlaceholderWithUnderscore(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :user_id');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertSame(':user_id', array_first($namedTokens)->getValue());
    }

    public function testTokenizeNamedPlaceholderWithNumbers(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :id1');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertSame(':id1', array_first($namedTokens)->getValue());
    }

    public function testTokenizeNamedPlaceholderStartingWithUnderscore(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE id = :_private');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertSame(':_private', array_first($namedTokens)->getValue());
    }

    public function testTokenizeUnicodeString(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = 'José' AND id = :id");

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertSame(':id', array_first($namedTokens)->getValue());
    }

    public function testTokenizeComplexQuery(): void
    {
        $result = $this->tokenizer->tokenize(
            "SELECT * FROM users WHERE id IN (:ids) AND name = 'test' AND status = ?"
        );

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());
        $positionalTokens = array_filter($result, static fn ($token) => TokenType::POSITIONAL === $token->getType());

        self::assertCount(1, $namedTokens);
        self::assertCount(1, $positionalTokens);
        self::assertSame(':ids', array_first($namedTokens)->getValue());
    }

    public function testTokenizeConsecutivePositionalPlaceholders(): void
    {
        $result = $this->tokenizer->tokenize('INSERT INTO users VALUES (?, ?, ?)');

        $positionalTokens = array_filter($result, static fn ($token) => TokenType::POSITIONAL === $token->getType());

        self::assertCount(3, $positionalTokens);
    }

    public function testTokenizeDuplicateNamedPlaceholders(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE name = :name OR alias = :name');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(2, $namedTokens);
    }

    public function testTokenizePreservesWhitespace(): void
    {
        $result = $this->tokenizer->tokenize("SELECT *\n\tFROM users\n\tWHERE id = :id");

        $textTokens = array_filter($result, static fn ($token) => TokenType::TEXT === $token->getType());
        $combinedText = implode('', array_map(static fn ($token) => $token->getValue(), $textTokens));

        self::assertStringContainsString("\n", $combinedText);
        self::assertStringContainsString("\t", $combinedText);
    }

    public function testTokenizeEmptyStringLiteral(): void
    {
        $result = $this->tokenizer->tokenize("SELECT * FROM users WHERE name = ''");

        self::assertCount(2, $result);
        self::assertSame(TokenType::TEXT, $result[0]->getType());
        self::assertSame(TokenType::TEXT, $result[1]->getType());
        self::assertSame("''", $result[1]->getValue());
    }

    public function testTokenizeColonNotFollowedByLetter(): void
    {
        $result = $this->tokenizer->tokenize('SELECT * FROM users WHERE time > 10:30');

        $namedTokens = array_filter($result, static fn ($token) => TokenType::NAMED === $token->getType());

        self::assertCount(0, $namedTokens);
    }

    public function testTokenizePositionalPlaceholderReturnsPositionalToken(): void
    {
        $result = $this->tokenizer->tokenize('?');

        self::assertCount(1, $result);
        self::assertTrue($result[0]->isPositional());
        self::assertFalse($result[0]->isNamed());
    }

    public function testTokenizeNamedPlaceholderReturnsNamedToken(): void
    {
        $result = $this->tokenizer->tokenize(':param');

        self::assertCount(1, $result);
        self::assertTrue($result[0]->isNamed());
        self::assertFalse($result[0]->isPositional());
        self::assertSame('param', $result[0]->getName());
    }
}
