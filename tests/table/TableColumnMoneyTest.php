<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnMoney;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnMoneyTest extends TableColumnTestCase
{
    public function noMappingDataProvider()
    {
        return [
            [['precision' => 10, 'scale' => 0], false, '$this->money(10, 0)'],
            [['precision' => 10, 'scale' => 0], true, '$this->money(10, 0)'],
            [['precision' => 19, 'scale' => 4], false, '$this->money(19, 4)'],
            [['precision' => 19, 'scale' => 4], true, '$this->money(19, 4)'],
        ];
    }

    /**
     * @dataProvider noMappingDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionNoMapping($column, $generalSchema, $result)
    {
        $column = new TableColumnMoney($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingDataProvider()
    {
        return [
            [['precision' => 10, 'scale' => 0], false, '$this->money(10, 0)'],
            [['precision' => 10, 'scale' => 0], true, '$this->money(10, 0)'],
            [['precision' => 19, 'scale' => 4], false, '$this->money(19, 4)'],
            [['precision' => 19, 'scale' => 4], true, '$this->money()'],
        ];
    }

    /**
     * @dataProvider withMappingDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithMapping($column, $generalSchema, $result)
    {
        $column['defaultMapping'] = 'decimal(19,4)';
        $column = new TableColumnMoney($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
