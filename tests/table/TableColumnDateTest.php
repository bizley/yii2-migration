<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDate;

class TableColumnDateTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumnDate();
        $this->assertEquals('$this->date()', $column->renderDefinition($this->getTable()));
    }
}
