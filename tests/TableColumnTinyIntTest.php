<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnTinyInt;

class TableColumnTinyIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnTinyInt(['size' => 1]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'tinyInteger(1)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnTinyInt(['size' => 10]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'tinyInteger()'
        ], 'definition', $column);
    }
}
