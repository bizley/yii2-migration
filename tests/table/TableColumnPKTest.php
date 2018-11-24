<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnPK(['size' => 11, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->primaryKey(11)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey()', $column->renderDefinition($this->getTable()));
    }
}
