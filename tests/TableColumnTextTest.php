<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnText;

class TableColumnTextTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnText(['size' => 1024]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'text(1024)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnText(['size' => 1024]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'text()'
        ], 'definition', $column);
    }
}
