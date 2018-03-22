<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumnInt;

class TableColumnIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumnInt(['size' => 11]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
            'integer(11)'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralComposite()
    {
        $column = new TableColumnInt(['size' => 11]);
        $column->renderDefinition($this->getTable(true, true));
        $this->assertAttributeEquals([
            '$this',
            'integer()'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralNotPK()
    {
        $column = new TableColumnInt(['size' => 11, 'name' => 'other']);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'integer()'
        ], 'definition', $column);
    }

    public function testDefinitionGeneralPK()
    {
        $column = new TableColumnInt(['size' => 11, 'name' => 'one']);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(true, 'isNotNullPossible', $column);
    }

    public function testDefinitionGeneralPKMSSQL()
    {
        $column = new TableColumnInt(['size' => 11, 'name' => 'one']);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'primaryKey()'
        ], 'definition', $column);
        $this->assertAttributeEquals(false, 'isNotNullPossible', $column);
    }
}
