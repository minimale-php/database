<?php

declare(strict_types=1);

namespace Minimale\Database\Tests;

use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\DriverRegistry;
use Minimale\Database\Exception\RegistryException;
use Mockery;
use Mockery\MockInterface;

final class DriverRegistryTest extends AbstractTestCase
{
    private DriverRegistry $registry;

    private DriverInterface&MockInterface $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new DriverRegistry();
        $this->driver = Mockery::mock(DriverInterface::class);
    }

    public function testAddRegistersDriverWithAlias(): void
    {
        $this->registry->add('mysql', $this->driver);

        self::assertTrue($this->registry->has('mysql'));
    }

    public function testAddThrowsExceptionWhenAliasAlreadyExists(): void
    {
        $this->registry->add('mysql', $this->driver);

        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Driver with alias "mysql" is already registered');

        $this->registry->add('mysql', Mockery::mock(DriverInterface::class));
    }

    public function testGetReturnsRegisteredDriver(): void
    {
        $this->registry->add('mysql', $this->driver);

        self::assertSame($this->driver, $this->registry->get('mysql'));
    }

    public function testGetThrowsExceptionWhenAliasNotFound(): void
    {
        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage('Driver with alias "mysql" not found in registry');

        $this->registry->get('mysql');
    }

    public function testHasReturnsTrueWhenDriverExists(): void
    {
        $this->registry->add('mysql', $this->driver);

        self::assertTrue($this->registry->has('mysql'));
    }

    public function testHasReturnsFalseWhenDriverDoesNotExist(): void
    {
        self::assertFalse($this->registry->has('mysql'));
    }

    public function testRemoveDeletesDriverFromRegistry(): void
    {
        $this->registry->add('mysql', $this->driver);

        $this->registry->remove('mysql');

        self::assertFalse($this->registry->has('mysql'));
    }

    public function testRemoveDoesNothingWhenAliasDoesNotExist(): void
    {
        $this->registry->remove('nonexistent');

        self::assertFalse($this->registry->has('nonexistent'));
    }

    public function testAllReturnsEmptyArrayWhenNoDriversRegistered(): void
    {
        self::assertSame([], $this->registry->all());
    }

    public function testAllReturnsAllRegisteredAliases(): void
    {
        $this->registry->add('mysql', $this->driver);
        $this->registry->add('pgsql', Mockery::mock(DriverInterface::class));
        $this->registry->add('sqlite', Mockery::mock(DriverInterface::class));

        self::assertSame(['mysql', 'pgsql', 'sqlite'], $this->registry->all());
    }
}
