<?php

declare(strict_types=1);

namespace Minimale\Database\Tests;

use Minimale\Database\DatabaseManager;
use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\Result;
use Mockery;
use Mockery\MockInterface;
use PDOStatement;

final class DatabaseManagerTest extends AbstractTestCase
{
    private DatabaseManager $manager;

    private DriverInterface&MockInterface $driver;

    private Result $result;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = Mockery::mock(DriverInterface::class);
        $statement = Mockery::mock(PDOStatement::class);
        $this->result = new Result($statement);
        $this->manager = new DatabaseManager($this->driver);
    }

    public function testExecuteReturnsResultFromDriver(): void
    {
        $this->driver->expects('execute')
            ->once()
            ->with('SELECT * FROM users', [])
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('SELECT * FROM users'));
    }

    public function testExecutePassesParametersToDriver(): void
    {
        $parameters = ['id' => 1, 'name' => 'John'];

        $this->driver->expects('execute')
            ->once()
            ->with('SELECT * FROM users WHERE id = :id AND name = :name', $parameters)
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('SELECT * FROM users WHERE id = :id AND name = :name', $parameters));
    }

    public function testExecutePassesEmptyParametersArrayByDefault(): void
    {
        $this->driver->expects('execute')
            ->once()
            ->with('SELECT 1', [])
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('SELECT 1'));
    }

    public function testExecuteWithPositionalParameters(): void
    {
        $parameters = [1, 'John'];

        $this->driver->expects('execute')
            ->once()
            ->with('SELECT * FROM users WHERE id = ? AND name = ?', $parameters)
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('SELECT * FROM users WHERE id = ? AND name = ?', $parameters));
    }

    public function testExecuteWithNullParameter(): void
    {
        $parameters = ['status' => null];

        $this->driver->expects('execute')
            ->once()
            ->with('UPDATE users SET status = :status', $parameters)
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('UPDATE users SET status = :status', $parameters));
    }

    public function testExecuteWithArrayParameter(): void
    {
        $parameters = ['ids' => [1, 2, 3]];

        $this->driver->expects('execute')
            ->once()
            ->with('SELECT * FROM users WHERE id IN (:ids)', $parameters)
            ->andReturns($this->result)
        ;

        self::assertSame($this->result, $this->manager->execute('SELECT * FROM users WHERE id IN (:ids)', $parameters));
    }

    public function testBeginTransactionDelegatesToDriver(): void
    {
        $this->driver->expects('beginTransaction')
            ->once()
        ;

        $this->manager->beginTransaction();

        self::expectNotToPerformAssertions();
    }

    public function testCommitDelegatesToDriver(): void
    {
        $this->driver->expects('commit')
            ->once()
        ;

        $this->manager->commit();

        self::expectNotToPerformAssertions();
    }

    public function testRollbackDelegatesToDriver(): void
    {
        $this->driver->expects('rollback')
            ->once()
        ;

        $this->manager->rollback();

        self::expectNotToPerformAssertions();
    }
}
