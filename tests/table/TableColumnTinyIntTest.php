<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTinyInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTinyIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnTinyInt(['size' => 1, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->tinyInteger(1)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength()
    {
        $column = new TableColumnTinyInt(['size' => 1]);
        $this->assertEquals('$this->tinyInteger()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnTinyInt(['size' => 10]);
        $this->assertEquals('$this->tinyInteger()', $column->renderDefinition($this->getTable()));
    }
}
