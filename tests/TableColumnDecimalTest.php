<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnDecimal;

class TableColumnDecimalTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale()
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'decimal(10, 7)'
        ], 'definition', $column);
    }

    public function testDefinitionSpecificPrecision()
    {
        $column = new TableColumnDecimal(['precision' => 5]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'decimal(5)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'decimal()'
        ], 'definition', $column);
    }
}
