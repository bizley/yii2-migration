<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnText;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTextTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnText(['size' => 1024, 'schema' => TableStructure::SCHEMA_MSSQL]);
        $this->assertEquals('$this->text(1024)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnText(['size' => 1024]);
        $this->assertEquals('$this->text()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnText(['size' => 1024]);
        $this->assertEquals('$this->text()', $column->renderDefinition($this->getTable()));
    }
}
