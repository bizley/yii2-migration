<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnChar;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnCharTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnChar(['size' => 10]);
        $this->assertEquals('$this->char(10)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnChar(['size' => 10]);
        $this->assertEquals('$this->char()', $column->renderDefinition($this->getTable()));
    }
}
