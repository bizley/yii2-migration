<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnFloat;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnFloatTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnFloat(['precision' => 4, 'schema' => TableStructure::SCHEMA_CUBRID]);
        $this->assertEquals('$this->float(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnFloat(['precision' => 4]);
        $this->assertEquals('$this->float()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnFloat(['precision' => 4]);
        $this->assertEquals('$this->float()', $column->renderDefinition($this->getTable()));
    }
}
