<?php

declare(strict_types=1);

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnTimestamp;

class TableColumnTimestampTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $this->assertEquals('$this->timestamp(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnTimestamp(['precision' => 4]);
        $this->assertEquals('$this->timestamp()', $column->renderDefinition($this->getTable()));
    }
}
