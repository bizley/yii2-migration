<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnUPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnUPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnUPK(['size' => 11, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->primaryKey(11)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnUPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnUPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey()->unsigned()', $column->renderDefinition($this->getTable(true)));
    }
}
