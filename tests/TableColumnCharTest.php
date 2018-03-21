<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnChar;

class TableColumnCharTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnChar(['size' => 10]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'char(10)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnChar(['size' => 10]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'char()'
        ], 'definition', $column);
    }
}
