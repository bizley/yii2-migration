<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDateTime;

class TableColumnDateTimeTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnDateTime(['precision' => 4]);
        $this->assertEquals('$this->dateTime(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnDateTime(['precision' => 4]);
        $this->assertEquals('$this->dateTime()', $column->renderDefinition($this->getTable()));
    }
}
