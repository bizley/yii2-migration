<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnChar;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnCharTest extends TableColumnTestCase
{
    public function noMappingDataProvider(): array
    {
        return [
            [['size' => 10], false, '$this->char(10)'],
            [['size' => 10], true, '$this->char(10)'],
            [['size' => 9], false, '$this->char(9)'],
            [['size' => 9], true, '$this->char(9)'],
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
        $column = new TableColumnChar($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingDataProvider(): array
    {
        return [
            [['size' => 10], false, '$this->char(10)'],
            [['size' => 10], true, '$this->char()'],
            [['size' => 9], false, '$this->char(9)'],
            [['size' => 9], true, '$this->char(9)'],
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
        $column['defaultMapping'] = 'char(10)';
        $column = new TableColumnChar($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
