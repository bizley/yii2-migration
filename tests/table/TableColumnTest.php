<?php

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumn;
use yii\db\Expression;

class TableColumnTest extends TableColumnTestCase
{
    public function testDefinition()
    {
        $column = new TableColumn(['size' => 11]);
        $this->assertEquals('$this', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionUnsigned()
    {
        $column = new TableColumn(['isUnsigned' => true]);
        $this->assertEquals('$this->unsigned()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionNotNull()
    {
        $column = new TableColumn(['isNotNull' => true]);
        $this->assertEquals('$this->notNull()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionDefaultValue()
    {
        $column = new TableColumn(['default' => "aaa'bbb"]);
        $this->assertEquals('$this->defaultValue(\'aaa\\\'bbb\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionDefaultExpression()
    {
        $column = new TableColumn(['default' => new Expression("TEST('aaa')")]);
        $this->assertEquals('$this->defaultExpression(\'TEST(\\\'aaa\\\')\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionComment()
    {
        $column = new TableColumn(['comment' => "aaa'bbb"]);
        $this->assertEquals('$this->comment(\'aaa\\\'bbb\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionPKAppend()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'AUTO_INCREMENT PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false)));
    }

    public function testDefinitionPKAppendMSSQL()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'IDENTITY PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema')));
    }

    public function testDefinitionPKAppendPGSQL()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\pgsql\Schema')));
    }

    public function testDefinitionPKAppendSQLITE()
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'PRIMARY KEY AUTOINCREMENT\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\sqlite\Schema')));
    }
}
