<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBinary;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBinaryTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBinary(['size' => 1, 'schema' => TableStructure::SCHEMA_MSSQL]);
        $this->assertEquals('$this->binary(1)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable()));
    }
}
