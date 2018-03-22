<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnMoney;

class TableColumnMoneyTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale()
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'money(10, 4)'
        ], 'definition', $column);
    }

    public function testDefinitionSpecificPrecision()
    {
        $column = new TableColumnMoney(['precision' => 5]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'money(5)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'money()'
        ], 'definition', $column);
    }
}
