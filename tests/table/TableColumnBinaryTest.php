<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBinary;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBinaryTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnBinary(['size' => 1, 'schema' => TableStructure::SCHEMA_MSSQL]);
        $this->assertEquals('$this->binary(1)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength(): void
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable()));
    }
}
