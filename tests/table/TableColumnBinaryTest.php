<?php

declare(strict_types=1);

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnBinary;

class TableColumnBinaryTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary(1)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable()));
    }
}
