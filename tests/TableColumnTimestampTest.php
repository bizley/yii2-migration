<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnTimestamp;

class TableColumnTimestampTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'timestamp(4)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'timestamp()'
        ], 'definition', $column);
    }
}
