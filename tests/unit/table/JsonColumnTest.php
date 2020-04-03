<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\JsonColumn;
use PHPUnit\Framework\TestCase;

final class JsonColumnTest extends TestCase
{
    /** @var JsonColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new JsonColumn();
    }

    /** @test */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('json()', $this->column->getDefinition());
    }

    public function providerForDefaults(): array
    {
        return [
            'string1' => ['', ''],
            'string2' => [null, null],
            'array' => [[1], [1]],
            'json null' => ['null', 'null'],
            'json proper1' => ['[1,2]', [1, 2]],
            'json proper2' => ['["a","b"]', ['a', 'b']],
            'json proper3' => ['{"a":1,"b":2}', ['a' => 1, 'b' => 2]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForDefaults
     * @param $defaultToSet
     * @param $expected
     */
    public function shouldReturnProperDefault($defaultToSet, $expected): void
    {
        $this->column->setDefault($defaultToSet);
        $this->assertSame($expected, $this->column->getDefault());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql' => [Schema::MYSQL, null],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     * @param string $schema
     * @param int|null $expected
     */
    public function shouldReturnProperLength(string $schema, ?int $expected): void
    {
        $this->assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql' => [Schema::MYSQL, null],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param int|null $expectedPrecision
     */
    public function shouldSetProperLength(string $schema, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /** @test */
    public function shouldNotDecodeJsonWhenThereIsException(): void
    {
        $this->column->setDefault('{"bad":"json');
        $this->assertSame('{"bad":"json', $this->column->getDefault());
    }
}
