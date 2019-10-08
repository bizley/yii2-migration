<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumn;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;
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
        $this->assertEquals(
            '$this->defaultExpression(\'TEST(\\\'aaa\\\')\')',
            $column->renderDefinition($this->getTable())
        );
    }

    public function testDefinitionDefaultArrayValue(): void
    {
        $column = new TableColumn(['default' => [1, 2, 3]]);
        $this->assertEquals('$this->defaultValue(\'[1,2,3]\')', $column->renderDefinition($this->getTable()));

        $column = new TableColumn(['default' => ['a' => 1, 'b' => 2, 'c' => 3]]);
        $this->assertEquals(
            '$this->defaultValue(\'{"a":1,"b":2,"c":3}\')',
            $column->renderDefinition($this->getTable())
        );
    }

    public function testDefinitionComment(): void
    {
        $column = new TableColumn(['comment' => "aaa'bbb"]);
        $this->assertEquals('$this->comment(\'aaa\\\'bbb\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionPKAppend(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true]);
        $this->assertEquals(
            '$this->append(\'AUTO_INCREMENT PRIMARY KEY\')',
            $column->renderDefinition($this->getTable(true, false))
        );
    }

    public function testDefinitionPKAppendMSSQL(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true, 'schema' => TableStructure::SCHEMA_MSSQL]);
        $this->assertEquals('$this->append(\'IDENTITY PRIMARY KEY\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionPKAppendPGSQL(): void
    {
        $column = new TableColumn(['name' => 'one', 'autoIncrement' => true, 'schema' => TableStructure::SCHEMA_PGSQL]);
        $this->assertEquals('$this->append(\'PRIMARY KEY\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionPKAppendSQLITE(): void
    {
        $column = new TableColumn([
            'name' => 'one',
            'autoIncrement' => true,
            'schema' => TableStructure::SCHEMA_SQLITE
        ]);
        $this->assertEquals(
            '$this->append(\'PRIMARY KEY AUTOINCREMENT\')',
            $column->renderDefinition($this->getTable())
        );
    }

    public function testDefinitionAfter(): void
    {
        $column = new TableColumn(['after' => 'columnAfter']);
        $this->assertEquals('$this->after(\'columnAfter\')', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionFirst(): void
    {
        $column = new TableColumn(['isFirst' => true]);
        $this->assertEquals('$this->first()', $column->renderDefinition($this->getTable()));
    }
}
