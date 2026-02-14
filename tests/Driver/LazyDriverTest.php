<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver;

use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\Driver\LazyDriver;
use Minimale\Database\Event\LazyConnectionRequestedEvent;
use Minimale\Database\Exception\ConnectionException;
use Minimale\Database\Result;
use Minimale\Database\Tests\AbstractTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

final class LazyDriverTest extends AbstractTestCase
{
    private const string ALIAS = 'client';

    private const string DSN = 'sqlite::memory:';

    private DriverInterface&MockInterface $innerDriver;

    private EventDispatcherInterface&MockInterface $eventDispatcher;

    private LazyDriver $lazyDriver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerDriver = Mockery::mock(DriverInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->lazyDriver = new LazyDriver($this->innerDriver, self::ALIAS, $this->eventDispatcher);
    }

    public function testExecuteDispatchesEventAndDelegatesToInnerDriver(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $result = new Result($statement);

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, 'user', 'pass')
                ;

                $event->getDriver()->connect(self::DSN, 'user', 'pass');

                return $event;
            })
        ;

        $this->innerDriver->expects('execute')
            ->once()
            ->with('SELECT 1', [])
            ->andReturns($result)
        ;

        self::assertSame($result, $this->lazyDriver->execute('SELECT 1'));
    }

    public function testBeginTransactionTriggersLazyConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, null, null)
                ;

                $event->getDriver()->connect(self::DSN);

                return $event;
            })
        ;

        $this->innerDriver->expects('beginTransaction')
            ->once()
        ;

        $this->lazyDriver->beginTransaction();
    }

    public function testCommitTriggersLazyConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, null, null)
                ;

                $event->getDriver()->connect(self::DSN);

                return $event;
            })
        ;

        $this->innerDriver->expects('commit')
            ->once()
        ;

        $this->lazyDriver->commit();
    }

    public function testRollbackTriggersLazyConnection(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, null, null)
                ;

                $event->getDriver()->connect(self::DSN);

                return $event;
            })
        ;

        $this->innerDriver->expects('rollback')
            ->once()
        ;

        $this->lazyDriver->rollback();
    }

    public function testExecuteThrowsConnectionExceptionWhenListenerDoesNotConnect(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(static fn (LazyConnectionRequestedEvent $event) => $event)
        ;

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Lazy connection for driver "client" could not be established');

        $this->lazyDriver->execute('SELECT 1');
    }

    public function testDirectConnectBypassesLazyMechanism(): void
    {
        $statement = Mockery::mock(PDOStatement::class);
        $result = new Result($statement);

        $this->innerDriver->expects('connect')
            ->once()
            ->with(self::DSN, 'user', 'pass')
        ;

        $this->lazyDriver->connect(self::DSN, 'user', 'pass');

        $this->eventDispatcher->expects('dispatch')
            ->never()
        ;

        $this->innerDriver->expects('execute')
            ->once()
            ->with('SELECT 1', [])
            ->andReturns($result)
        ;

        self::assertSame($result, $this->lazyDriver->execute('SELECT 1'));
    }

    public function testConnectPreventsDoubleConnection(): void
    {
        $this->innerDriver->expects('connect')
            ->once()
            ->with(self::DSN, null, null)
        ;

        $this->lazyDriver->connect(self::DSN);
        $this->lazyDriver->connect(self::DSN);
    }

    public function testDisconnectDelegatesToInnerDriverWhenConnected(): void
    {
        $this->innerDriver->expects('connect')
            ->once()
            ->with(self::DSN, null, null)
        ;

        $this->innerDriver->expects('disconnect')
            ->once()
        ;

        $this->lazyDriver->connect(self::DSN);
        $this->lazyDriver->disconnect();
    }

    public function testDisconnectDoesNothingWhenNotConnected(): void
    {
        $this->innerDriver->expects('disconnect')
            ->never()
        ;

        $this->lazyDriver->disconnect();
    }

    public function testDisconnectAllowsReconnection(): void
    {
        $this->innerDriver->expects('connect')
            ->once()
            ->with(self::DSN, null, null)
        ;

        $this->innerDriver->expects('disconnect')
            ->once()
        ;

        $this->lazyDriver->connect(self::DSN);
        $this->lazyDriver->disconnect();

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, 'user', 'pass')
                ;

                $event->getDriver()->connect(self::DSN, 'user', 'pass');

                return $event;
            })
        ;

        $statement = Mockery::mock(PDOStatement::class);
        $result = new Result($statement);

        $this->innerDriver->expects('execute')
            ->once()
            ->with('SELECT 1', [])
            ->andReturns($result)
        ;

        self::assertSame($result, $this->lazyDriver->execute('SELECT 1'));
    }

    public function testEventContainsCorrectAliasAndDriverReference(): void
    {
        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(Mockery::type(LazyConnectionRequestedEvent::class))
            ->andReturnUsing(function (LazyConnectionRequestedEvent $event) {
                self::assertSame(self::ALIAS, $event->getAlias());
                self::assertSame($this->lazyDriver, $event->getDriver());

                $this->innerDriver->expects('connect')
                    ->once()
                    ->with(self::DSN, null, null)
                ;

                $event->getDriver()->connect(self::DSN);

                return $event;
            })
        ;

        $statement = Mockery::mock(PDOStatement::class);
        $result = new Result($statement);

        $this->innerDriver->expects('execute')
            ->once()
            ->with('SELECT 1', [])
            ->andReturns($result)
        ;

        $this->lazyDriver->execute('SELECT 1');
    }
}
