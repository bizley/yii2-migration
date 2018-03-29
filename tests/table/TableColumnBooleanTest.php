<?php

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnBoolean;

class TableColumnBooleanTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumnBoolean();
        $this->assertEquals('$this->boolean()', $column->renderDefinition($this->getTable()));
    }
}
