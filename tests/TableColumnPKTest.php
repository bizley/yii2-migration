<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnPK;

class TableColumnPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnPK(['size' => 11]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey(11)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnPK(['size' => 11]);
        $column->renderDefinition($this->getTable(true));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isPkPossible', $column);
    }

    public function testDefinitionGeneralMSSQL()
    {
        $column = new TableColumnPK(['size' => 11]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
    }
}
