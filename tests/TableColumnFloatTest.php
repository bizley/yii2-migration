<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnFloat;

class TableColumnFloatTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnFloat(['precision' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'float(4)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnFloat(['precision' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'float()'
        ], 'definition', $column);
    }
}
