<?php

namespace bizley\migration\tests;

use bizley\migration\table\TableColumn;
use yii\db\Expression;

class TableColumnTest extends TableColumnTestCase
{
    public function testDefinitionSpecific()
    {
        $column = new TableColumn(['size' => 11]);
        $column->renderDefinition($this->getTable(false));
        $this->assertAttributeEquals([
            '$this',
        ], 'definition', $column);
    }

    public function testDefinitionGeneral()
    {
        $column = new TableColumn(['size' => 11]);
        $column->renderDefinition($this->getTable(true));
        $this->assertAttributeEquals([
            '$this',
        ], 'definition', $column);
    }

    public function testDefinitionUnsigned()
    {
        $column = new TableColumn(['isUnsigned' => true]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'unsigned()',
        ], 'definition', $column);
    }

    public function testDefinitionNotNull()
    {
        $column = new TableColumn(['isNotNull' => true]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'notNull()',
        ], 'definition', $column);
    }

    public function testDefinitionDefaultValue()
    {
        $column = new TableColumn(['default' => "aaa'bbb"]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'defaultValue(\'aaa\\\'bbb\')',
        ], 'definition', $column);
    }

    public function testDefinitionDefaultExpression()
    {
        $column = new TableColumn(['default' => new Expression("TEST('aaa')")]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'defaultExpression(\'TEST(\\\'aaa\\\')\')',
        ], 'definition', $column);
    }

    public function testDefinitionComment()
    {
        $column = new TableColumn(['comment' => "aaa'bbb"]);
        $column->renderDefinition($this->getTable());
        $this->assertAttributeEquals([
            '$this',
            'comment(\'aaa\\\'bbb\')',
        ], 'definition', $column);
    }

    public function testDefinitionPKAppend()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $column->renderDefinition($this->getTable(true, false));
        $this->assertAttributeEquals([
            '$this',
            'append(\'AUTO_INCREMENT PRIMARY KEY\')'
        ], 'definition', $column);
    }

    public function testDefinitionPKAppendMSSQL()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'append(\'IDENTITY PRIMARY KEY\')'
        ], 'definition', $column);
    }

    public function testDefinitionPKAppendPGSQL()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\pgsql\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'append(\'PRIMARY KEY\')'
        ], 'definition', $column);
    }

    public function testDefinitionPKAppendSQLITE()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $column->renderDefinition($this->getTable(true, false, 'yii\db\sqlite\Schema'));
        $this->assertAttributeEquals([
            '$this',
            'append(\'PRIMARY KEY AUTOINCREMENT\')'
        ], 'definition', $column);
    }
}
