<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnString;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnStringTest extends TableColumnTestCase
{
    public function noMappingDataProvider(): array
    {
        return [
            [['size' => 255], false, '$this->string(255)'],
            [['size' => 255], true, '$this->string(255)'],
            [['size' => 20], false, '$this->string(20)'],
            [['size' => 20], true, '$this->string(20)'],
        ];
    }

    /**
     * @dataProvider noMappingDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionNoMapping(array $column, bool $generalSchema, string $result): void
    {
        $column = new TableColumnString($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingDataProvider(): array
    {
        return [
            [['size' => 255], false, '$this->string(255)'],
            [['size' => 255], true, '$this->string()'],
            [['size' => 20], false, '$this->string(20)'],
            [['size' => 20], true, '$this->string(20)'],
        ];
    }

    /**
     * @dataProvider withMappingDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithMapping(array $column, bool $generalSchema, string $result): void
    {
        $column['defaultMapping'] = 'varchar(255)';
        $column = new TableColumnString($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
