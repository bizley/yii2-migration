<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnInt(['size' => 11, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->integer(11)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnInt(['size' => 11]);
        $this->assertEquals('$this->integer()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneralComposite()
    {
        $column = new TableColumnInt(['size' => 11]);
        $this->assertEquals('$this->integer()', $column->renderDefinition($this->getTable(true, true)));
    }

    public function testDefinitionGeneralNotPK()
    {
        $column = new TableColumnInt(['size' => 11, 'name' => 'other']);
        $this->assertEquals('$this->integer()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionGeneralPK()
    {
        $column = new TableColumnInt(['size' => 11, 'name' => 'one']);
        $this->assertEquals('$this->primaryKey()', $column->renderDefinition($this->getTable()));
    }
}
