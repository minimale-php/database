<?php

declare(strict_types=1);

namespace Minimale\Database\Tests;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Exception\ResultException;
use Minimale\Database\Result;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOException;
use PDOStatement;

final class ResultTest extends AbstractTestCase
{
    private PDOStatement&MockInterface $statement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statement = Mockery::mock(PDOStatement::class);
    }

    public function testFetchReturnsNullWhenNoRowsAvailable(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturnFalse()
        ;

        $result = new Result($this->statement);

        self::assertNull($result->fetch());
    }

    public function testFetchReturnsRowWithoutTransformer(): void
    {
        $row = ['id' => 1, 'name' => 'John'];

        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturns($row)
        ;

        $result = new Result($this->statement);

        self::assertSame($row, $result->fetch());
    }

    public function testFetchReturnsTransformedRowWithTransformer(): void
    {
        $row = ['id' => 1, 'name' => 'John'];
        $transformedRow = ['id' => 1, 'name' => 'JOHN'];

        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturns($row)
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')
            ->once()
            ->with($row)
            ->andReturns($transformedRow)
        ;

        $result = new Result($this->statement, $transformer);

        self::assertSame($transformedRow, $result->fetch());
    }

    public function testFetchThrowsResultExceptionOnPDOException(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andThrow(new PDOException())
        ;

        $result = new Result($this->statement);

        $this->expectException(ResultException::class);
        $this->expectExceptionMessage('Could not fetch row');

        $result->fetch();
    }

    public function testFetchValueReturnsNullWhenNoRowsAvailable(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturnFalse()
        ;

        $result = new Result($this->statement);

        self::assertNull($result->fetchValue());
    }

    public function testFetchValueReturnsFirstColumnValue(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturns([42, 'ignored'])
        ;

        $result = new Result($this->statement);

        self::assertSame(42, $result->fetchValue());
    }

    public function testFetchValueReturnsNullWhenFirstColumnIsNull(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturns([null, 'other'])
        ;

        $result = new Result($this->statement);

        self::assertNull($result->fetchValue());
    }

    public function testFetchValueReturnsTransformedValueWithTransformer(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturns(['test', 'ignored'])
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')
            ->once()
            ->with('test')
            ->andReturns('TEST')
        ;

        $result = new Result($this->statement, $transformer);

        self::assertSame('TEST', $result->fetchValue());
    }

    public function testFetchValueThrowsResultExceptionOnPDOException(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andThrow(new PDOException())
        ;

        $result = new Result($this->statement);

        $this->expectException(ResultException::class);
        $this->expectExceptionMessage('Could not fetch value');

        $result->fetchValue();
    }

    public function testFetchAllReturnsEmptyArrayWhenNoRows(): void
    {
        $this->statement->expects('fetchAll')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturns([])
        ;

        $result = new Result($this->statement);

        self::assertSame([], $result->fetchAll());
    }

    public function testFetchAllReturnsRowsWithoutTransformer(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $this->statement->expects('fetchAll')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturns($rows)
        ;

        $result = new Result($this->statement);

        self::assertSame($rows, $result->fetchAll());
    }

    public function testFetchAllReturnsTransformedRowsWithTransformer(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $this->statement->expects('fetchAll')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturns($rows)
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')
            ->once()
            ->with($rows[0])
            ->andReturns(['id' => 1, 'name' => 'JOHN'])
        ;

        $transformer->expects('decode')
            ->once()
            ->with($rows[1])
            ->andReturns(['id' => 2, 'name' => 'JANE'])
        ;

        $result = new Result($this->statement, $transformer);

        $expected = [
            ['id' => 1, 'name' => 'JOHN'],
            ['id' => 2, 'name' => 'JANE'],
        ];

        self::assertSame($expected, $result->fetchAll());
    }

    public function testFetchAllThrowsResultExceptionOnPDOException(): void
    {
        $this->statement->expects('fetchAll')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andThrow(new PDOException())
        ;

        $result = new Result($this->statement);

        $this->expectException(ResultException::class);
        $this->expectExceptionMessage('Could not fetch rows');

        $result->fetchAll();
    }

    public function testRowCountReturnsAffectedRowCount(): void
    {
        $this->statement->expects('rowCount')
            ->once()
            ->andReturns(5)
        ;

        $result = new Result($this->statement);

        self::assertSame(5, $result->rowCount());
    }

    public function testRowCountReturnsZeroWhenNoRowsAffected(): void
    {
        $this->statement->expects('rowCount')
            ->once()
            ->andReturns(0)
        ;

        $result = new Result($this->statement);

        self::assertSame(0, $result->rowCount());
    }

    public function testRowCountThrowsResultExceptionOnPDOException(): void
    {
        $this->statement->expects('rowCount')
            ->once()
            ->andThrow(new PDOException())
        ;

        $result = new Result($this->statement);

        $this->expectException(ResultException::class);
        $this->expectExceptionMessage('Could not get the row count');

        $result->rowCount();
    }

    public function testFetchDoesNotCallTransformerWhenNoRows(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturnFalse()
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')->never();

        $result = new Result($this->statement, $transformer);

        self::assertNull($result->fetch());
    }

    public function testFetchValueDoesNotCallTransformerWhenNoRows(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturnFalse()
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')->never();

        $result = new Result($this->statement, $transformer);

        self::assertNull($result->fetchValue());
    }

    public function testFetchValueDoesNotCallTransformerWhenValueIsNull(): void
    {
        $this->statement->expects('fetch')
            ->once()
            ->with(PDO::FETCH_NUM)
            ->andReturns([null])
        ;

        $transformer = Mockery::mock(DataTransformerInterface::class);
        $transformer->expects('decode')->never();

        $result = new Result($this->statement, $transformer);

        self::assertNull($result->fetchValue());
    }
}
