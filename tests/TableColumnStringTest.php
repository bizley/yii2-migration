<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnString;

class TableColumnStringTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnString(['size' => 255]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'string(255)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnString(['size' => 255]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'string()'
        ], 'definition', $column);
    }
}
