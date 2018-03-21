<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnBinary;

class TableColumnBinaryTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'binary(1)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBinary(['size' => 1]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'binary()'
        ], 'definition', $column);
    }
}
