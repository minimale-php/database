<?php

declare(strict_types=1);

namespace Minimale\Database\Tests;

use Minimale\Database\Driver\FirebirdDriver;
use Minimale\Database\Driver\SQLiteDriver;
use Minimale\Database\DriverFactory;
use Minimale\Database\Exception\DriverException;
use Mockery;
use Psr\EventDispatcher\EventDispatcherInterface;

final class DriverFactoryTest extends AbstractTestCase
{
    public function testCreateReturnsSQLiteDriverForSqliteDsn(): void
    {
        $driver = DriverFactory::create(['dsn' => 'sqlite::memory:']);

        self::assertInstanceOf(SQLiteDriver::class, $driver);
    }

    public function testCreateReturnsFirebirdDriverForFirebirdDsn(): void
    {
        $driver = DriverFactory::create(['dsn' => 'firebird:dbname=localhost:/path/to/database']);

        self::assertInstanceOf(FirebirdDriver::class, $driver);
    }

    public function testCreatePassesEventDispatcherToDriver(): void
    {
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $driver = DriverFactory::create(['dsn' => 'sqlite::memory:'], $eventDispatcher);

        self::assertInstanceOf(SQLiteDriver::class, $driver);
    }

    public function testCreateThrowsExceptionWhenDsnIsMissing(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('DSN is required in configuration');

        DriverFactory::create([]);
    }

    public function testCreateThrowsExceptionForUnsupportedDriverType(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unsupported driver type: mysql');

        DriverFactory::create(['dsn' => 'mysql:host=localhost;dbname=test']);
    }

    public function testCreateThrowsExceptionForInvalidDsnFormat(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Invalid DSN format: invalid_dsn');

        DriverFactory::create(['dsn' => 'invalid_dsn']);
    }

    public function testCreateIsCaseInsensitiveForDriverType(): void
    {
        $driver = DriverFactory::create(['dsn' => 'SQLITE::memory:']);

        self::assertInstanceOf(SQLiteDriver::class, $driver);
    }

    public function testCreateHandlesDsnWithMultipleColons(): void
    {
        $driver = DriverFactory::create(['dsn' => 'sqlite:path:with:colons.db']);

        self::assertInstanceOf(SQLiteDriver::class, $driver);
    }
}
