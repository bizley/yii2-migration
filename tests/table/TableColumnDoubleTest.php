<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDoubleTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnDouble(['precision' => 4, 'schema' => TableStructure::SCHEMA_CUBRID]);
        $this->assertEquals('$this->double(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double()', $column->renderDefinition($this->getTable()));
    }
}
