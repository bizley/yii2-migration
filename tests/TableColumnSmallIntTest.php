<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnSmallInt;

class TableColumnSmallIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnSmallInt(['size' => 6]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'smallInteger(6)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnSmallInt(['size' => 10]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'smallInteger()'
        ], 'definition', $column);
    }
}
