<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver\QueryNormalizer;

use Minimale\Database\Driver\QueryNormalizer\DefaultQueryNormalizer;
use Minimale\Database\Driver\QueryNormalizer\RegexQueryTokenizer;
use Minimale\Database\Exception\QueryNormalizerException;
use Minimale\Database\Tests\AbstractTestCase;
use Override;
use stdClass;

final class DefaultQueryNormalizerTest extends AbstractTestCase
{
    private DefaultQueryNormalizer $normalizer;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new DefaultQueryNormalizer(new RegexQueryTokenizer());
    }

    public function testItExpandsNamedArrayPlaceholder(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids)', ['ids' => [10, 20, 30]]);

        self::assertSame('SELECT * FROM users WHERE id IN (:ids_1, :ids_2, :ids_3)', $result->getQuery());
        self::assertSame(['ids_1' => 10, 'ids_2' => 20, 'ids_3' => 30], $result->getParameters());
    }

    public function testItExpandsSingleElementArray(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids)', ['ids' => [42]]);

        self::assertSame('SELECT * FROM users WHERE id IN (:ids_1)', $result->getQuery());
        self::assertSame(['ids_1' => 42], $result->getParameters());
    }

    public function testItThrowsOnEmptyArrayForNamedParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not expand an empty array for named parameter "ids"');

        $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids)', ['ids' => []]);
    }

    public function testItThrowsOnEmptyArrayForPositionalParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not expand an empty array for positional parameter at index 0');

        $this->normalizer->normalize('SELECT * FROM users WHERE id IN (?)', [[]]);
    }

    public function testItThrowsOnNonScalarValueInArrayForNamedParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Only scalar values are allowed in arrays');

        $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids)', ['ids' => [1, ['nested'], 3]]);
    }

    public function testItThrowsOnNonScalarValueInArrayForPositionalParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Only scalar values are allowed in arrays');

        $this->normalizer->normalize('SELECT * FROM users WHERE id IN (?)', [[1, new stdClass(), 3]]);
    }

    public function testItExpandsArrayWithMixedScalarTypes(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:values)', ['values' => [1, 'two', 3.14]]);

        self::assertSame('SELECT * FROM users WHERE id IN (:values_1, :values_2, :values_3)', $result->getQuery());
        self::assertSame(['values_1' => 1, 'values_2' => 'two', 'values_3' => 3.14], $result->getParameters());
    }

    public function testItExpandsMultipleDifferentArrays(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE id IN (:ids) AND status IN (:statuses)',
            ['ids' => [1, 2], 'statuses' => ['active', 'pending']],
        );

        self::assertSame(
            'SELECT * FROM users WHERE id IN (:ids_1, :ids_2) AND status IN (:statuses_1, :statuses_2)',
            $result->getQuery(),
        );

        self::assertSame(
            ['ids_1' => 1, 'ids_2' => 2, 'statuses_1' => 'active', 'statuses_2' => 'pending'],
            $result->getParameters(),
        );
    }

    public function testItKeepsUniqueScalarPlaceholdersUnchanged(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE name = :name AND age > :age',
            ['name' => 'John', 'age' => 25],
        );

        self::assertSame('SELECT * FROM users WHERE name = :name AND age > :age', $result->getQuery());
        self::assertSame(['name' => 'John', 'age' => 25], $result->getParameters());
    }

    public function testItHandlesNullParameter(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE deleted_at IS :deleted', ['deleted' => null]);

        self::assertSame('SELECT * FROM users WHERE deleted_at IS :deleted', $result->getQuery());
        self::assertSame(['deleted' => null], $result->getParameters());
    }

    public function testItHandlesFloatParameter(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM products WHERE price > :price', ['price' => 19.99]);

        self::assertSame('SELECT * FROM products WHERE price > :price', $result->getQuery());
        self::assertSame(['price' => 19.99], $result->getParameters());
    }

    public function testItHandlesBooleanParameter(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE active = :active', ['active' => true]);

        self::assertSame('SELECT * FROM users WHERE active = :active', $result->getQuery());
        self::assertSame(['active' => true], $result->getParameters());
    }

    public function testItRenamesDuplicateScalarPlaceholders(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE name = :name AND alias = :name', ['name' => 'John']);

        self::assertSame('SELECT * FROM users WHERE name = :name_1 AND alias = :name_2', $result->getQuery());
        self::assertSame(['name_1' => 'John', 'name_2' => 'John'], $result->getParameters());
    }

    public function testItRenamesTripleDuplicateScalarPlaceholders(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM t WHERE a = :x OR b = :x OR c = :x', ['x' => 'val']);

        self::assertSame('SELECT * FROM t WHERE a = :x_1 OR b = :x_2 OR c = :x_3', $result->getQuery());
        self::assertSame(['x_1' => 'val', 'x_2' => 'val', 'x_3' => 'val'], $result->getParameters());
    }

    public function testItExpandsDuplicateArrayPlaceholdersIndependently(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids) OR parent_id IN (:ids)', ['ids' => [1, 2, 3]]);

        self::assertSame(
            'SELECT * FROM users WHERE id IN (:ids_1, :ids_2, :ids_3) OR parent_id IN (:ids_4, :ids_5, :ids_6)',
            $result->getQuery(),
        );

        self::assertSame(
            ['ids_1' => 1, 'ids_2' => 2, 'ids_3' => 3, 'ids_4' => 1, 'ids_5' => 2, 'ids_6' => 3],
            $result->getParameters(),
        );
    }

    public function testItHandlesMixedArrayAndDuplicateScalars(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE id IN (:ids) AND name = :name AND age > :age AND name = :name',
            ['ids' => [1, 2, 3], 'name' => 'John', 'age' => 25],
        );

        self::assertSame(
            'SELECT * FROM users WHERE id IN (:ids_1, :ids_2, :ids_3) AND name = :name_1 AND age > :age AND name = :name_2',
            $result->getQuery(),
        );

        self::assertSame(
            ['ids_1' => 1, 'ids_2' => 2, 'ids_3' => 3, 'name_1' => 'John', 'age' => 25, 'name_2' => 'John'],
            $result->getParameters(),
        );
    }

    public function testItHandlesDuplicateNullPlaceholders(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM t WHERE a IS :val OR b IS :val', ['val' => null]);

        self::assertSame('SELECT * FROM t WHERE a IS :val_1 OR b IS :val_2', $result->getQuery());
        self::assertSame(['val_1' => null, 'val_2' => null], $result->getParameters());
    }

    public function testItConvertsPositionalScalarToNamed(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE name = ? AND age > ?', ['John', 25]);

        self::assertSame('SELECT * FROM users WHERE name = :param_1 AND age > :param_2', $result->getQuery());
        self::assertSame(['param_1' => 'John', 'param_2' => 25], $result->getParameters());
    }

    public function testItExpandsPositionalArrayPlaceholder(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (?)', [[10, 20, 30]]);

        self::assertSame('SELECT * FROM users WHERE id IN (:param_1, :param_2, :param_3)', $result->getQuery());
        self::assertSame(['param_1' => 10, 'param_2' => 20, 'param_3' => 30], $result->getParameters());
    }

    public function testItExpandsMultiplePositionalArrays(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (?) AND status IN (?)', [[1, 2], ['active', 'inactive']]);

        self::assertSame(
            'SELECT * FROM users WHERE id IN (:param_1, :param_2) AND status IN (:param_3, :param_4)',
            $result->getQuery(),
        );

        self::assertSame(
            ['param_1' => 1, 'param_2' => 2, 'param_3' => 'active', 'param_4' => 'inactive'],
            $result->getParameters(),
        );
    }

    public function testItHandlesMixedPositionalScalarsAndArrays(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE name = ? AND id IN (?) AND age > ?',
            ['John', [1, 2, 3], 25],
        );

        self::assertSame(
            'SELECT * FROM users WHERE name = :param_1 AND id IN (:param_2, :param_3, :param_4) AND age > :param_5',
            $result->getQuery(),
        );

        self::assertSame(
            ['param_1' => 'John', 'param_2' => 1, 'param_3' => 2, 'param_4' => 3, 'param_5' => 25],
            $result->getParameters(),
        );
    }

    public function testItHandlesMixedPositionalAndNamedPlaceholders(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE id = ? AND name = :name AND age = ?',
            [42, 'name' => 'John', 99],
        );

        self::assertSame(
            'SELECT * FROM users WHERE id = :param_1 AND name = :name AND age = :param_2',
            $result->getQuery(),
        );

        self::assertSame(['param_1' => 42, 'name' => 'John', 'param_2' => 99], $result->getParameters());
    }

    public function testItIgnoresNamedPlaceholderInsideSingleQuotedString(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE label = ':name' AND id IN (:ids)", ['ids' => [1, 2]]);

        self::assertSame("SELECT * FROM users WHERE label = ':name' AND id IN (:ids_1, :ids_2)", $result->getQuery());
        self::assertSame(['ids_1' => 1, 'ids_2' => 2], $result->getParameters());
    }

    public function testItIgnoresNamedPlaceholderInsideDoubleQuotedString(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE label = ":name" AND id IN (:ids)', ['ids' => [1, 2]]);

        self::assertSame('SELECT * FROM users WHERE label = ":name" AND id IN (:ids_1, :ids_2)', $result->getQuery());
        self::assertSame(['ids_1' => 1, 'ids_2' => 2], $result->getParameters());
    }

    public function testItIgnoresQuestionMarkInsideSingleQuotedString(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE label = 'what?' AND id = ?", [42]);

        self::assertSame("SELECT * FROM users WHERE label = 'what?' AND id = :param_1", $result->getQuery());
        self::assertSame(['param_1' => 42], $result->getParameters());
    }

    public function testItIgnoresQuestionMarkInsideDoubleQuotedString(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE label = "what?" AND id = ?', [42]);

        self::assertSame('SELECT * FROM users WHERE label = "what?" AND id = :param_1', $result->getQuery());
        self::assertSame(['param_1' => 42], $result->getParameters());
    }

    public function testItIgnoresPlaceholdersInAdjacentStringLiterals(): void
    {
        $result = $this->normalizer->normalize(
            "SELECT * FROM t WHERE a = 'hello' AND b = :b AND c = 'world :fake' AND d = :d",
            ['b' => 1, 'd' => 2],
        );

        self::assertSame(
            "SELECT * FROM t WHERE a = 'hello' AND b = :b AND c = 'world :fake' AND d = :d",
            $result->getQuery(),
        );

        self::assertSame(['b' => 1, 'd' => 2], $result->getParameters());
    }

    public function testItHandlesSqlStandardEscapedSingleQuotes(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE name = 'it''s :not_a_param' AND id = :id", ['id' => 1]);

        self::assertSame("SELECT * FROM users WHERE name = 'it''s :not_a_param' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesBackslashEscapedSingleQuotes(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE name = 'it\\'s :not_a_param' AND id = :id", ['id' => 1]);

        self::assertSame("SELECT * FROM users WHERE name = 'it\\'s :not_a_param' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesSqlStandardEscapedDoubleQuotes(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE name = "say ""hello"" :fake" AND id = :id', ['id' => 1]);

        self::assertSame('SELECT * FROM users WHERE name = "say ""hello"" :fake" AND id = :id', $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesBackslashEscapedDoubleQuotes(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE name = "say \\"hello\\" :fake" AND id = :id', ['id' => 1]);

        self::assertSame(
            'SELECT * FROM users WHERE name = "say \\"hello\\" :fake" AND id = :id',
            $result->getQuery(),
        );

        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesEscapedBackslashAtEndOfString(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM t WHERE path = 'C:\\\\' AND id = :id", ['id' => 1]);

        self::assertSame("SELECT * FROM t WHERE path = 'C:\\\\' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesMultipleEscapedQuotesInSameString(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM t WHERE x = 'a''b''c :fake' AND id = :id", ['id' => 5]);

        self::assertSame("SELECT * FROM t WHERE x = 'a''b''c :fake' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 5], $result->getParameters());
    }

    public function testItPreservesGermanUnicodeCharacters(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO users (name) VALUES ('Jürgen'), ('Verschlußdeckel') WHERE id = :id", ['id' => 1]);

        self::assertSame(
            "INSERT INTO users (name) VALUES ('Jürgen'), ('Verschlußdeckel') WHERE id = :id",
            $result->getQuery(),
        );

        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItPreservesSpanishUnicodeCharacters(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO users (name) VALUES ('María') WHERE id = :id", ['id' => 1]);

        self::assertSame("INSERT INTO users (name) VALUES ('María') WHERE id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItPreservesChineseUnicodeCharacters(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO users (name) VALUES ('李华') WHERE id = :id", ['id' => 1]);

        self::assertSame("INSERT INTO users (name) VALUES ('李华') WHERE id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItPreservesMixedUnicodeInSameQuery(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO users (name) VALUES ('Jürgen'), ('María'), ('李华'), ('Verschlußdeckel')", []);

        self::assertSame(
            "INSERT INTO users (name) VALUES ('Jürgen'), ('María'), ('李华'), ('Verschlußdeckel')",
            $result->getQuery(),
        );

        self::assertSame([], $result->getParameters());
    }

    public function testItPreservesEmojiInStringLiteral(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO posts (body) VALUES ('Hello 🌍🎉') WHERE id = :id", ['id' => 1]);

        self::assertSame("INSERT INTO posts (body) VALUES ('Hello 🌍🎉') WHERE id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItPreservesArabicAndHebrewCharacters(): void
    {
        $result = $this->normalizer->normalize("INSERT INTO users (name) VALUES ('محمد'), ('דוד') WHERE id = :id", ['id' => 1]);

        self::assertSame("INSERT INTO users (name) VALUES ('محمد'), ('דוד') WHERE id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItIgnoresPostgresqlCastSyntax(): void
    {
        $result = $this->normalizer->normalize('SELECT created_at::date FROM users WHERE id = :id', ['id' => 1]);

        self::assertSame('SELECT created_at::date FROM users WHERE id = :id', $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItIgnoresMultiplePostgresqlCasts(): void
    {
        $result = $this->normalizer->normalize('SELECT created_at::date, amount::numeric::money FROM orders WHERE id = :id', ['id' => 1]);

        self::assertSame(
            'SELECT created_at::date, amount::numeric::money FROM orders WHERE id = :id',
            $result->getQuery(),
        );

        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesJoinWithArrayAndScalar(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users u JOIN orders o ON u.id = o.user_id WHERE u.id IN (:ids) AND o.amount > :amount',
            ['ids' => [1, 2, 3], 'amount' => 100.50],
        );

        self::assertSame(
            'SELECT * FROM users u JOIN orders o ON u.id = o.user_id WHERE u.id IN (:ids_1, :ids_2, :ids_3) AND o.amount > :amount',
            $result->getQuery(),
        );

        self::assertSame(
            ['ids_1' => 1, 'ids_2' => 2, 'ids_3' => 3, 'amount' => 100.50],
            $result->getParameters(),
        );
    }

    public function testItHandlesSubqueryWithArray(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders WHERE status IN (:statuses)) AND age > :age',
            ['statuses' => ['active', 'pending'], 'age' => 18],
        );

        self::assertSame(
            'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders WHERE status IN (:statuses_1, :statuses_2)) AND age > :age',
            $result->getQuery(),
        );

        self::assertSame(
            ['statuses_1' => 'active', 'statuses_2' => 'pending', 'age' => 18],
            $result->getParameters(),
        );
    }

    public function testItHandlesMultipleJoinsWithDuplicates(): void
    {
        $result = $this->normalizer->normalize(
            'SELECT * FROM users u JOIN orders o ON u.id = o.user_id JOIN payments p ON o.id = p.order_id WHERE u.status = :status AND o.status = :status AND p.amount > :amount',
            ['status' => 'active', 'amount' => 50],
        );

        self::assertSame(
            'SELECT * FROM users u JOIN orders o ON u.id = o.user_id JOIN payments p ON o.id = p.order_id WHERE u.status = :status_1 AND o.status = :status_2 AND p.amount > :amount',
            $result->getQuery(),
        );

        self::assertSame(
            ['status_1' => 'active', 'status_2' => 'active', 'amount' => 50],
            $result->getParameters(),
        );
    }

    public function testItHandlesQueryWithNoPlaceholders(): void
    {
        $result = $this->normalizer->normalize('SELECT 1', []);

        self::assertSame('SELECT 1', $result->getQuery());
        self::assertSame([], $result->getParameters());
    }

    public function testItHandlesQueryWithNoParametersAndStringLiterals(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE name = 'John'", []);

        self::assertSame("SELECT * FROM users WHERE name = 'John'", $result->getQuery());
        self::assertSame([], $result->getParameters());
    }

    public function testItHandlesEmptyStringParameter(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE name = :name', ['name' => '']);

        self::assertSame('SELECT * FROM users WHERE name = :name', $result->getQuery());
        self::assertSame(['name' => ''], $result->getParameters());
    }

    public function testItHandlesPlaceholderWithUnderscores(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE first_name = :first_name', ['first_name' => 'John']);

        self::assertSame('SELECT * FROM users WHERE first_name = :first_name', $result->getQuery());
        self::assertSame(['first_name' => 'John'], $result->getParameters());
    }

    public function testItHandlesPlaceholderStartingWithUnderscore(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id = :_id', ['_id' => 1]);

        self::assertSame('SELECT * FROM users WHERE id = :_id', $result->getQuery());
        self::assertSame(['_id' => 1], $result->getParameters());
    }

    public function testItHandlesPlaceholderWithNumbers(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id = :id1 AND name = :name2', ['id1' => 1, 'name2' => 'John']);

        self::assertSame('SELECT * FROM users WHERE id = :id1 AND name = :name2', $result->getQuery());
        self::assertSame(['id1' => 1, 'name2' => 'John'], $result->getParameters());
    }

    public function testItHandlesConsecutiveStringLiterals(): void
    {
        $result = $this->normalizer->normalize("SELECT CONCAT('hello', ' ', 'world') WHERE id = :id", ['id' => 1]);

        self::assertSame("SELECT CONCAT('hello', ' ', 'world') WHERE id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesEmptyStringLiteral(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM users WHERE name != '' AND id = :id", ['id' => 1]);

        self::assertSame("SELECT * FROM users WHERE name != '' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItPreservesNewlinesAndWhitespaceInQuery(): void
    {
        $query = "SELECT *\nFROM users\nWHERE id = :id\n  AND name = :name";

        $result = $this->normalizer->normalize($query, ['id' => 1, 'name' => 'John']);

        self::assertSame($query, $result->getQuery());
        self::assertSame(['id' => 1, 'name' => 'John'], $result->getParameters());
    }

    public function testItHandlesLargeNumberOfArrayElements(): void
    {
        $ids = range(1, 100);

        $result = $this->normalizer->normalize('SELECT * FROM users WHERE id IN (:ids)', ['ids' => $ids]);

        $expectedPlaceholders = implode(', ', array_map(
            static fn (int $i): string => ":ids_{$i}",
            range(1, 100),
        ));

        self::assertSame("SELECT * FROM users WHERE id IN ({$expectedPlaceholders})", $result->getQuery());
        self::assertCount(100, $result->getParameters());

        foreach ($ids as $index => $id) {
            self::assertSame($id, $result->getParameters()['ids_'.($index + 1)]);
        }
    }

    public function testItHandlesColonInsideStringThatIsNotAPlaceholder(): void
    {
        $result = $this->normalizer->normalize("SELECT * FROM events WHERE time = '12:30:00' AND id = :id", ['id' => 1]);

        self::assertSame("SELECT * FROM events WHERE time = '12:30:00' AND id = :id", $result->getQuery());
        self::assertSame(['id' => 1], $result->getParameters());
    }

    public function testItHandlesOnlyPositionalPlaceholders(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE a = ? AND b = ? AND c = ?', [1, 'two', 3.0]);

        self::assertSame(
            'SELECT * FROM users WHERE a = :param_1 AND b = :param_2 AND c = :param_3',
            $result->getQuery(),
        );

        self::assertSame(['param_1' => 1, 'param_2' => 'two', 'param_3' => 3.0], $result->getParameters());
    }

    public function testItHandlesPositionalNullParameter(): void
    {
        $result = $this->normalizer->normalize('SELECT * FROM users WHERE deleted_at IS ?', [null]);

        self::assertSame('SELECT * FROM users WHERE deleted_at IS :param_1', $result->getQuery());
        self::assertSame(['param_1' => null], $result->getParameters());
    }

    public function testItThrowsOnMissingNamedParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not find a named parameter "name"');

        $this->normalizer->normalize('SELECT * FROM users WHERE name = :name', []);
    }

    public function testItThrowsOnMissingOneOfMultipleNamedParameters(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not find a named parameter "age"');

        $this->normalizer->normalize('SELECT * FROM users WHERE name = :name AND age > :age', ['name' => 'John']);
    }

    public function testItThrowsOnMissingPositionalParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not find a positional parameter at index 0');

        $this->normalizer->normalize('SELECT * FROM users WHERE id = ?', []);
    }

    public function testItThrowsOnMissingSecondPositionalParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not find a positional parameter at index 1');

        $this->normalizer->normalize('SELECT * FROM users WHERE id = ? AND name = ?', [1]);
    }

    public function testItThrowsOnTypoInNamedParameter(): void
    {
        $this->expectException(QueryNormalizerException::class);
        $this->expectExceptionMessage('Could not find a named parameter "naem"');

        $this->normalizer->normalize('SELECT * FROM users WHERE name = :naem', ['name' => 'John']);
    }
}
