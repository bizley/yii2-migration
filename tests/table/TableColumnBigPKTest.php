<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBigPK;

class TableColumnBigPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnBigPK(['size' => 20]);
        $this->assertEquals('$this->bigPrimaryKey(20)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnBigPK(['size' => 20]);
        $this->assertEquals('$this->bigPrimaryKey()', $column->renderDefinition($this->getTable(true)));
    }
}
