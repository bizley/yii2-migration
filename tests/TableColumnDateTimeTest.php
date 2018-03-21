<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnDateTime;

class TableColumnDateTimeTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnDateTime(['precision' => 4]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'dateTime(4)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDateTime(['precision' => 4]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'dateTime()'
        ], 'definition', $column);
    }
}
