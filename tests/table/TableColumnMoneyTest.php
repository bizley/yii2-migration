<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnMoney;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnMoneyTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale(): void
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $this->assertEquals('$this->money(10, 4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecision(): void
    {
        $column = new TableColumnMoney(['precision' => 5]);
        $this->assertEquals('$this->money(5)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnMoney(['precision' => 10, 'scale' => 4]);
        $this->assertEquals('$this->money()', $column->renderDefinition($this->getTable()));
    }
}
