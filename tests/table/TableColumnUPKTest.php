<?php

declare(strict_types=1);

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnUPK;

class TableColumnUPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnUPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey(11)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnUPK(['size' => 11]);
        $this->assertEquals('$this->primaryKey()->unsigned()', $column->renderDefinition($this->getTable(true)));
    }
}
