<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnDate;

class TableColumnDateTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumnDate();
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'date()'
        ], 'definition', $column);
    }
}
