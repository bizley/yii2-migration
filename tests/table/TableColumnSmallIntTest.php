<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnSmallInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnSmallIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnSmallInt(['size' => 6, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->smallInteger(6)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnSmallInt(['size' => 6]);
        $this->assertEquals('$this->smallInteger()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnSmallInt(['size' => 10]);
        $this->assertEquals('$this->smallInteger()', $column->renderDefinition($this->getTable()));
    }
}
