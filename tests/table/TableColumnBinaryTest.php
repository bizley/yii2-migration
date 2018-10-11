<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBinary;

class TableColumnBinaryTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary(1)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $this->assertEquals('$this->binary()', $column->renderDefinition($this->getTable()));
    }
}
