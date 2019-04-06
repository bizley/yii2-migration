<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTime;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTimeTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnTime(['precision' => 4, 'schema' => TableStructure::SCHEMA_PGSQL]);
        $this->assertEquals('$this->time(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength(): void
    {
        $column = new TableColumnTime(['precision' => 4]);
        $this->assertEquals('$this->time()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnTime(['precision' => 4]);
        $this->assertEquals('$this->time()', $column->renderDefinition($this->getTable()));
    }
}
