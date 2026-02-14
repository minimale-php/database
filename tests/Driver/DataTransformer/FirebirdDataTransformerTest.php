<?php

declare(strict_types=1);

namespace Minimale\Database\Tests\Driver\DataTransformer;

use Minimale\Database\Driver\DataTransformer\FirebirdDataTransformer;
use Minimale\Database\Tests\AbstractTestCase;
use Override;

final class FirebirdDataTransformerTest extends AbstractTestCase
{
    private FirebirdDataTransformer $dataTransformer;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dataTransformer = new FirebirdDataTransformer();
    }

    public function testEncodeConvertsUtf8ToWindows1252(): void
    {
        $utf8Cedilla = hex2bin('c3a7');

        $result = $this->dataTransformer->encode($utf8Cedilla);

        self::assertSame('e7', bin2hex($result));
    }

    public function testDecodeConvertsWindows1252ToUtf8(): void
    {
        $windows1252Cedilla = hex2bin('e7');

        $result = $this->dataTransformer->decode($windows1252Cedilla);

        self::assertSame('c3a7', bin2hex($result));
    }

    public function testEncodeReturnsNullUnchanged(): void
    {
        $result = $this->dataTransformer->encode(null);

        self::assertNull($result);
    }

    public function testDecodeReturnsNullUnchanged(): void
    {
        $result = $this->dataTransformer->decode(null);

        self::assertNull($result);
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

    public function testEncodeConvertsArrayStrings(): void
    {
        $utf8Cedilla = hex2bin('c3a7');

        $value = [
            'key1' => $utf8Cedilla,
            'key2' => 42,
            'key3' => null,
        ];

        $result = $this->dataTransformer->encode($value);

        self::assertSame('e7', bin2hex($result['key1']));
        self::assertSame(42, $result['key2']);
        self::assertNull($result['key3']);
    }

    public function testDecodeConvertsArrayStrings(): void
    {
        $windows1252Cedilla = hex2bin('e7');

        $value = [
            'key1' => $windows1252Cedilla,
            'key2' => 42,
            'key3' => null,
        ];

        $result = $this->dataTransformer->decode($value);

        self::assertSame('c3a7', bin2hex($result['key1']));
        self::assertSame(42, $result['key2']);
        self::assertNull($result['key3']);
    }

    public function testEncodeReturnsEmptyStringUnchanged(): void
    {
        $value = '';

        $result = $this->dataTransformer->encode($value);

        self::assertSame('', $result);
    }

    public function testDecodeReturnsEmptyStringUnchanged(): void
    {
        $value = '';

        $result = $this->dataTransformer->decode($value);

        self::assertSame('', $result);
    }

    public function testEncodeReturnsAsciiStringUnchanged(): void
    {
        $value = 'hello world';

        $result = $this->dataTransformer->encode($value);

        self::assertSame($value, $result);
    }

    public function testDecodeReturnsAsciiStringUnchanged(): void
    {
        $value = 'hello world';

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

    public function testEncodeConvertsMultipleSpecialCharacters(): void
    {
        $utf8Value = hex2bin('c3a0c3a9c3b1'); // corresponds to àéñ in UTF-8

        $result = $this->dataTransformer->encode($utf8Value);

        self::assertSame('e0e9f1', bin2hex($result));
    }

    public function testDecodeConvertsMultipleSpecialCharacters(): void
    {
        $windows1252Value = hex2bin('e0e9f1'); // corresponds to àéñ in Windows-1252

        $result = $this->dataTransformer->decode($windows1252Value);

        self::assertSame('c3a0c3a9c3b1', bin2hex($result));
    }

    public function testEncodeAndDecodeAreInverse(): void
    {
        $utf8Value = hex2bin('c3a7c3a3c3b5'); // corresponds to çãõ in UTF-8

        $encoded = $this->dataTransformer->encode($utf8Value);
        $decoded = $this->dataTransformer->decode($encoded);

        self::assertSame(bin2hex($utf8Value), bin2hex($decoded));
    }
}
