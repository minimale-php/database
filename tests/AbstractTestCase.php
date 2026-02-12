<?php

declare(strict_types=1);

namespace Minimale\Database\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }
}
