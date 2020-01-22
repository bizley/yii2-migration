<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\Column;
use PHPUnit\Framework\TestCase;

class ColumnTestCase extends TestCase
{
    /** @var string */
    protected static $column;

    private function getColumn(array $config = []): Column
    {
        return new static::$column($config);
    }

    public function providerForSettingLength(): array
    {
        return [];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param string|int $size
     * @param string|int $precision
     */
    public function shouldProperlySetLength(string $schema, $size, $precision): void
    {
        $column = $this->getColumn(['schema' => $schema]);
        $column->setLength(1);

        $this->assertSame($size, $column->size);
        $this->assertSame($precision, $column->precision);
    }

    public function providerForGettingLength(): array
    {
        return [];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     * @param string $schema
     * @param string|int $length
     */
    public function shouldProperlyGetLength(string $schema, $length): void
    {
        $column = $this->getColumn(['schema' => $schema]);
        $column->size = 1;

        $this->assertSame($length, $column->getLength());
    }
}
