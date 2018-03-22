<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnUPK;

class TableColumnUPKTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnUPK(['size' => 11]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey(11)',
        ], 'definition', $column);
        $this->assertAttributeEquals(true, 'isUnsignedPossible', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumnUPK(['size' => 11]);
        $column->renderDefinition($this->getTable(true));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()',
            'unsigned()',
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isPkPossible', $column);
        $this->assertAttributeEquals(false, 'isUnsignedPossible', $column);
    }

    public function testDefinitionGeneralMSSQL()
    {
        $column = new TableColumnUPK(['size' => 11]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()',
            'unsigned()',
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
        $this->assertAttributeEquals(false, 'isUnsignedPossible', $column);
    }
}
