<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnTime;

class TableColumnTimeTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnTime(['precision' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'time(4)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnTime(['precision' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'time()'
        ], 'definition', $column);
    }
}
