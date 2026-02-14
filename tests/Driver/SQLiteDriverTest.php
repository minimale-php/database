<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver;

use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;
use Minimale\Database\Driver\QueryNormalizer\NormalizedQuery;
use Minimale\Database\Driver\QueryNormalizer\QueryNormalizerInterface;
use Minimale\Database\Driver\SQLiteDriver;
use Minimale\Database\Event\ConnectionClosedEvent;
use Minimale\Database\Event\ConnectionEstablishedEvent;
use Minimale\Database\Event\QueryExecutedEvent;
use Minimale\Database\Event\TransactionBeganEvent;
use Minimale\Database\Event\TransactionCommittedEvent;
use Minimale\Database\Event\TransactionRolledBackEvent;
use Minimale\Database\Exception\ConnectionException;
use Minimale\Database\Exception\QueryException;
use Minimale\Database\Exception\TransactionException;
use Minimale\Database\Result;
use Minimale\Database\Tests\AbstractTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;

final class SQLiteDriverTest extends AbstractTestCase
{
    private const string DSN = 'sqlite::memory:';

    private SQLiteDriver $driver;

    private EventDispatcherInterface&MockInterface $eventDispatcher;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->driver = new SQLiteDriver($this->eventDispatcher);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->disconnect();

        parent::tearDown();
    }

    public function testConnectEstablishesConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(ConnectionEstablishedEvent::class))
        ;

        $this->driver->connect(self::DSN);

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(QueryExecutedEvent::class))
        ;

        $result = $this->driver->execute('SELECT 1');

        self::assertInstanceOf(Result::class, $result);
    }

    public function testConnectThrowsExceptionForInvalidDSN(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not connect to the SQLite database');

        $this->driver->connect('sqlite:/nonexistent/path/database.db');
    }

    public function testConnectDispatchesConnectionEstablishedEvent(): void
    {
        $dispatchedEvent = null;
        $this->eventDispatcher->allows('dispatch')
            ->andReturnUsing(static function ($event) use (&$dispatchedEvent): void {
                if ($event instanceof ConnectionEstablishedEvent) {
                    $dispatchedEvent = $event;
                }
            })
        ;

        $this->driver->connect(self::DSN);

        self::assertInstanceOf(ConnectionEstablishedEvent::class, $dispatchedEvent);
        self::assertSame(self::DSN, $dispatchedEvent->getDSN());
    }

    public function testExecuteReturnsResult(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $result = $this->driver->execute('SELECT 1 as value');

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(['value' => 1], $result->fetch());
    }

    public function testExecuteThrowsExceptionWithoutConnection(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->driver->execute('SELECT 1');
    }

    public function testExecuteDispatchesQueryExecutedEvent(): void
    {
        $dispatchedEvent = null;
        $this->eventDispatcher->allows('dispatch')
            ->andReturnUsing(static function ($event) use (&$dispatchedEvent): void {
                if ($event instanceof QueryExecutedEvent) {
                    $dispatchedEvent = $event;
                }
            })
        ;

        $this->driver->connect(self::DSN);

        $this->driver->execute('SELECT :param as value', ['param' => 'test']);

        self::assertInstanceOf(QueryExecutedEvent::class, $dispatchedEvent);
        self::assertSame('SELECT :param as value', $dispatchedEvent->getQuery());
        self::assertSame(['param' => 'test'], $dispatchedEvent->getParameters());
        self::assertGreaterThan(0, $dispatchedEvent->getExecutionTime());
        self::assertLessThan(1.0, $dispatchedEvent->getExecutionTime());
    }

    public function testExecuteWithParameters(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->driver->execute('INSERT INTO test (id, name) VALUES (:id, :name)', ['id' => 1, 'name' => 'Alice']);

        $result = $this->driver->execute('SELECT * FROM test WHERE id = :id', ['id' => 1]);

        self::assertSame(['id' => 1, 'name' => 'Alice'], $result->fetch());
    }

    public function testExecuteWithCustomDataTransformer(): void
    {
        $dataTransformer = Mockery::mock(DataTransformerInterface::class);
        $dataTransformer->expects('encode')
            ->once()
            ->with('test')
            ->andReturns('ENCODED')
        ;

        $driver = new SQLiteDriver($this->eventDispatcher, $dataTransformer);

        $this->eventDispatcher->allows('dispatch');
        $driver->connect(self::DSN);

        $result = $driver->execute('SELECT :param as value', ['param' => 'test']);

        self::assertInstanceOf(Result::class, $result);
    }

    public function testExecuteWithCustomQueryNormalizer(): void
    {
        $queryNormalizer = Mockery::mock(QueryNormalizerInterface::class);
        $queryNormalizer->expects('normalize')
            ->once()
            ->with('SELECT ? as value', [0 => 'test'])
            ->andReturns(new NormalizedQuery('SELECT :p0 as value', [':p0' => 'test']))
        ;

        $driver = new SQLiteDriver($this->eventDispatcher, null, $queryNormalizer);

        $this->eventDispatcher->allows('dispatch');
        $driver->connect(self::DSN);

        $result = $driver->execute('SELECT ? as value', [0 => 'test']);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(['value' => 'test'], $result->fetch());
    }

    public function testExecuteThrowsQueryExceptionOnInvalidQuery(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Could not execute query');

        $this->driver->execute('INVALID SQL QUERY');
    }

    public function testDisconnectClosesConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(ConnectionEstablishedEvent::class))
        ;

        $this->driver->connect(self::DSN);

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(ConnectionClosedEvent::class))
        ;

        $this->driver->disconnect();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->driver->execute('SELECT 1');
    }

    public function testDisconnectDoesNothingWithoutConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')->never();

        $this->driver->disconnect();
    }

    public function testBeginTransactionStartsTransaction(): void
    {
        $dispatchedEvent = null;
        $this->eventDispatcher->allows('dispatch')
            ->andReturnUsing(static function ($event) use (&$dispatchedEvent): void {
                if ($event instanceof TransactionBeganEvent) {
                    $dispatchedEvent = $event;
                }
            })
        ;

        $this->driver->connect(self::DSN);

        $this->driver->beginTransaction();

        self::assertInstanceOf(TransactionBeganEvent::class, $dispatchedEvent);
    }

    public function testBeginTransactionThrowsExceptionWithoutConnection(): void
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->driver->beginTransaction();
    }

    public function testCommitCommitsTransaction(): void
    {
        $dispatchedEvent = null;
        $this->eventDispatcher->allows('dispatch')
            ->andReturnUsing(static function ($event) use (&$dispatchedEvent): void {
                if ($event instanceof TransactionCommittedEvent) {
                    $dispatchedEvent = $event;
                }
            })
        ;

        $this->driver->connect(self::DSN);
        $this->driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->driver->beginTransaction();
        $this->driver->execute('INSERT INTO test (id, name) VALUES (1, "Alice")');

        $this->driver->commit();

        self::assertInstanceOf(TransactionCommittedEvent::class, $dispatchedEvent);

        $result = $this->driver->execute('SELECT * FROM test WHERE id = 1');
        self::assertSame(['id' => 1, 'name' => 'Alice'], $result->fetch());
    }

    public function testCommitThrowsExceptionWithoutConnection(): void
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->driver->commit();
    }

    public function testRollbackRevertsTransaction(): void
    {
        $dispatchedEvent = null;
        $this->eventDispatcher->allows('dispatch')
            ->andReturnUsing(static function ($event) use (&$dispatchedEvent): void {
                if ($event instanceof TransactionRolledBackEvent) {
                    $dispatchedEvent = $event;
                }
            })
        ;

        $this->driver->connect(self::DSN);
        $this->driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->driver->beginTransaction();
        $this->driver->execute('INSERT INTO test (id, name) VALUES (1, "Alice")');

        $this->driver->rollback();

        self::assertInstanceOf(TransactionRolledBackEvent::class, $dispatchedEvent);

        $result = $this->driver->execute('SELECT * FROM test WHERE id = 1');
        self::assertNull($result->fetch());
    }

    public function testRollbackThrowsExceptionWithoutConnection(): void
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->driver->rollback();
    }

    public function testDriverWorksWithoutEventDispatcher(): void
    {
        $driver = new SQLiteDriver();
        $driver->connect(self::DSN);

        $result = $driver->execute('SELECT 1 as value');

        self::assertSame(['value' => 1], $result->fetch());

        $driver->disconnect();
    }

    public function testFullTransactionWorkflow(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->execute('CREATE TABLE accounts (id INTEGER PRIMARY KEY, balance INTEGER)');
        $this->driver->execute('INSERT INTO accounts (id, balance) VALUES (1, 100)');
        $this->driver->execute('INSERT INTO accounts (id, balance) VALUES (2, 200)');

        $this->driver->beginTransaction();
        $this->driver->execute('UPDATE accounts SET balance = balance - 50 WHERE id = 1');
        $this->driver->execute('UPDATE accounts SET balance = balance + 50 WHERE id = 2');
        $this->driver->commit();

        $result1 = $this->driver->execute('SELECT balance FROM accounts WHERE id = 1');
        $result2 = $this->driver->execute('SELECT balance FROM accounts WHERE id = 2');

        self::assertSame(['balance' => 50], $result1->fetch());
        self::assertSame(['balance' => 250], $result2->fetch());
    }

    public function testBeginTransactionThrowsExceptionWhenTransactionAlreadyActive(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->beginTransaction();

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Could not begin transaction');

        $this->driver->beginTransaction();
    }

    public function testCommitThrowsExceptionWhenNoTransactionActive(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Could not commit transaction');

        $this->driver->commit();
    }

    public function testRollbackThrowsExceptionWhenNoTransactionActive(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Could not roll back transaction');

        $this->driver->rollback();
    }

    public function testExecuteWithNullParameter(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->driver->execute('INSERT INTO test (id, name) VALUES (:id, :name)', ['id' => 1, 'name' => null]);

        $result = $this->driver->execute('SELECT * FROM test WHERE id = :id', ['id' => 1]);

        self::assertSame(['id' => 1, 'name' => null], $result->fetch());
    }

    public function testExecuteWithBooleanParameters(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->execute('CREATE TABLE flags (id INTEGER PRIMARY KEY, active INTEGER, deleted INTEGER)');
        $this->driver->execute(
            'INSERT INTO flags (id, active, deleted) VALUES (:id, :active, :deleted)',
            ['id' => 1, 'active' => true, 'deleted' => false]
        );

        $result = $this->driver->execute('SELECT * FROM flags WHERE id = :id', ['id' => 1]);

        self::assertSame(['id' => 1, 'active' => 1, 'deleted' => ''], $result->fetch());
    }

    public function testExecuteWithBooleanTrueCastsToOne(): void
    {
        $this->eventDispatcher->allows('dispatch');
        $this->driver->connect(self::DSN);

        $this->driver->execute('CREATE TABLE bool_test (id INTEGER PRIMARY KEY, flag INTEGER)');
        $this->driver->execute(
            'INSERT INTO bool_test (id, flag) VALUES (:id, :flag)',
            ['id' => 1, 'flag' => true]
        );

        $result = $this->driver->execute('SELECT flag FROM bool_test WHERE id = :id', ['id' => 1]);

        self::assertSame(1, $result->fetchValue());
    }

    public function testTransactionsWorkWithoutEventDispatcher(): void
    {
        $driver = new SQLiteDriver();
        $driver->connect(self::DSN);

        $driver->beginTransaction();
        $driver->rollback();

        $driver->beginTransaction();
        $driver->commit();

        $driver->disconnect();

        self::expectNotToPerformAssertions();
    }
}
