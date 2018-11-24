<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBoolean;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBooleanTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumnBoolean();
        $this->assertEquals('$this->boolean()', $column->renderDefinition($this->getTable()));
    }
}
