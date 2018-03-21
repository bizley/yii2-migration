<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnBigInt;

class TableColumnBigIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnBigInt(['size' => 20]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'bigInteger(20)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralComposite()
    {
        $column = new TableColumnBigInt(['size' => 20]);
        $column->renderDefinition($this->getTable(true, true));
        $this->assertAttributeEquals([
            '$this',
            'bigInteger()'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralNotPK()
    {
        $column = new TableColumnBigInt(['size' => 20, 'name' => 'other']);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'bigInteger()'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralPK()
    {
        $column = new TableColumnBigInt(['size' => 20, 'name' => 'one']);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(true, 'isNotNullPossible', $column);
    }

    public function testDefinitionGeneralPKMSSQL()
    {
        $column = new TableColumnBigInt(['size' => 20, 'name' => 'one']);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'bigPrimaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
    }
}
