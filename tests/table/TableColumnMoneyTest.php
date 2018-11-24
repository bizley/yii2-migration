<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnMoney;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnMoneyTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale()
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $this->assertEquals('$this->money(10, 4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecision()
    {
        $column = new TableColumnMoney(['precision' => 5]);
        $this->assertEquals('$this->money(5)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $this->assertEquals('$this->money()', $column->renderDefinition($this->getTable()));
    }
}
