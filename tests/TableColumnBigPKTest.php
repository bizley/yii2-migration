<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnBigPK;

class TableColumnBigPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBigPK(['size' => 20]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey(20)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBigPK(['size' => 20]);
        $column->renderDefinition($this->getTable(true));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isPkPossible', $column);
    }

    public function testDefinitionGeneralMSSQL()
    {
        $column = new TableColumnBigPK(['size' => 20]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
    }
}
