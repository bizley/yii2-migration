<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnBoolean;

class TableColumnBooleanTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumnBoolean();
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'boolean()'
        ], 'definition', $column);
    }
}
