<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDecimal;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDecimalTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale()
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->decimal(10, 7)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecisionScaleNoLength()
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $this->assertEquals('$this->decimal()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecision()
    {
        $column = new TableColumnDecimal(['precision' => 5, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->decimal(5)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecisionNoLength()
    {
        $column = new TableColumnDecimal(['precision' => 5]);
        $this->assertEquals('$this->decimal()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $this->assertEquals('$this->decimal()', $column->renderDefinition($this->getTable()));
    }
}
