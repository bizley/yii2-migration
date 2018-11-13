<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTimestamp;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTimestampTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnTimestamp(['precision' => 4, 'schema' => TableStructure::SCHEMA_PGSQL]);
        $this->assertEquals('$this->timestamp(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength(): void
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $this->assertEquals('$this->timestamp()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $this->assertEquals('$this->timestamp()', $column->renderDefinition($this->getTable()));
    }
}
