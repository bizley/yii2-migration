<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDouble;

class TableColumnDoubleTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double()', $column->renderDefinition($this->getTable()));
    }
}
