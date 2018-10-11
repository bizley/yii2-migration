<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumn;
use yii\db\Expression;

class TableColumnTest extends TableColumnTestCase
{
    public function testDefinition(): void
    {
        $column = new TableColumn(['size' => 11]);
        $this->assertEquals('$this', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionUnsigned(): void
    {
        $column = new TableColumn(['isUnsigned' => true]);
        $this->assertEquals('$this->unsigned()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionNotNull(): void
    {
        $column = new TableColumn(['isNotNull' => true]);
        $this->assertEquals('$this->notNull()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionDefaultValue(): void
    {
        $column = new TableColumn(['default' => "aaa'bbb"]);
        $this->assertEquals('$this->defaultValue(\'aaa\\\'bbb\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionDefaultExpression(): void
    {
        $column = new TableColumn(['default' => new Expression("TEST('aaa')")]);
        $this->assertEquals('$this->defaultExpression(\'TEST(\\\'aaa\\\')\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionComment(): void
    {
        $column = new TableColumn(['comment' => "aaa'bbb"]);
        $this->assertEquals('$this->comment(\'aaa\\\'bbb\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionPKAppend(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'AUTO_INCREMENT PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false)));
    }

    public function testDefinitionPKAppendMSSQL(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'IDENTITY PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\mssql\Schema')));
    }

    public function testDefinitionPKAppendPGSQL(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'PRIMARY KEY\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\pgsql\Schema')));
    }

    public function testDefinitionPKAppendSQLITE(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals('$this->append(\'PRIMARY KEY AUTOINCREMENT\')', $column->renderDefinition($this->getTable(true, false, 'yii\db\sqlite\Schema')));
    }
}
