<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnSmallInt;

class TableColumnSmallIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnSmallInt(['size' => 6]);
        $this->assertEquals('$this->smallInteger(6)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnSmallInt(['size' => 10]);
        $this->assertEquals('$this->smallInteger()', $column->renderDefinition($this->getTable()));
    }
}
