<?php

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnString;

class TableColumnStringTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnString(['size' => 255]);
        $this->assertEquals('$this->string(255)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnString(['size' => 255]);
        $this->assertEquals('$this->string()', $column->renderDefinition($this->getTable()));
    }
}
