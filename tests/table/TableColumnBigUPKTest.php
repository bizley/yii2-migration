<?php

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnBigUPK;

class TableColumnBigUPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBigUPK(['size' => 20]);
        $this->assertEquals('$this->bigPrimaryKey(20)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBigUPK(['size' => 20]);
        $this->assertEquals('$this->bigPrimaryKey()->unsigned()', $column->renderDefinition($this->getTable(true)));
    }
}
