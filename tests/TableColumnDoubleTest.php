<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnDouble;

class TableColumnDoubleTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'double(4)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'double()'
        ], 'definition', $column);
    }
}
