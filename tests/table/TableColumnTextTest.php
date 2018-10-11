<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnText;

class TableColumnTextTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnText(['size' => 1024]);
        $this->assertEquals('$this->text(1024)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnText(['size' => 1024]);
        $this->assertEquals('$this->text()', $column->renderDefinition($this->getTable()));
    }
}
