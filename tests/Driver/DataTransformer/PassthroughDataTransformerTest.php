<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver\DataTransformer;

use Minimale\Database\Driver\DataTransformer\PassthroughDataTransformer;
use Minimale\Database\Tests\AbstractTestCase;
use Override;

final class PassthroughDataTransformerTest extends AbstractTestCase
{
    private PassthroughDataTransformer $dataTransformer;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dataTransformer = new PassthroughDataTransformer();
    }

    public function testEncodeReturnsStringUnchanged(): void
    {
        $value = 'test string';

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsStringUnchanged(): void
    {
        $value = 'test string';

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsIntegerUnchanged(): void
    {
        $value = 42;

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsIntegerUnchanged(): void
    {
        $value = 42;

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsFloatUnchanged(): void
    {
        $value = 3.14;

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsFloatUnchanged(): void
    {
        $value = 3.14;

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsNullUnchanged(): void
    {
        $value = null;

        $result = $this->dataTransformer->encode($value);

        self::assertNull($result);
    }

    public function testDecodeReturnsNullUnchanged(): void
    {
        $value = null;

        $result = $this->dataTransformer->decode($value);

        self::assertNull($result);
    }

    public function testEncodeReturnsBooleanUnchanged(): void
    {
        $value = true;

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsBooleanUnchanged(): void
    {
        $value = false;

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsArrayUnchanged(): void
    {
        $value = [
            'key1' => 'value1',
            'key2' => 42,
            'key3' => null,
        ];

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsArrayUnchanged(): void
    {
        $value = [
            'key1' => 'value1',
            'key2' => 42,
            'key3' => null,
        ];

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsEmptyStringUnchanged(): void
    {
        $value = '';

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsEmptyStringUnchanged(): void
    {
        $value = '';

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsZeroUnchanged(): void
    {
        $value = 0;

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsZeroUnchanged(): void
    {
        $value = 0;

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsEmptyArrayUnchanged(): void
    {
        $value = [];

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsEmptyArrayUnchanged(): void
    {
        $value = [];

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }

    public function testEncodeReturnsNegativeNumberUnchanged(): void
    {
        $value = -123;

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsNegativeNumberUnchanged(): void
    {
        $value = -123;

        $result = $this->dataTransformer->decode($value);

        self::assertSame($value, $result);
    }
}
