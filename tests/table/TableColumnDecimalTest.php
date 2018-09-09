<?php

declare(strict_types=1);

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnDecimal;

class TableColumnDecimalTest extends TableColumnTestCase
{
    public function testDefinitionSpecificPrecisionScale(): void
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $this->assertEquals('$this->decimal(10, 7)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificPrecision(): void
    {
        $column = new TableColumnDecimal(['precision' => 5]);
        $this->assertEquals('$this->decimal(5)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnDecimal(['precision' => 10, 'scale' => 7]);
        $this->assertEquals('$this->decimal()', $column->renderDefinition($this->getTable()));
    }
}
