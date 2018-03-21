<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnBigUPK;

class TableColumnBigUPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBigUPK(['size' => 20]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey(20)',
        ], 'definition', $column);
        $this->assertAttributeEquals(true, 'isUnsignedPossible', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnBigUPK(['size' => 20]);
        $column->renderDefinition($this->getTable(true));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()',
            'unsigned()',
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isPkPossible', $column);
        $this->assertAttributeEquals(false, 'isUnsignedPossible', $column);
    }

    public function testDefinitionGeneralMSSQL()
    {
        $column = new TableColumnBigUPK(['size' => 20]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()',
            'unsigned()',
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
        $this->assertAttributeEquals(false, 'isUnsignedPossible', $column);
    }
}
