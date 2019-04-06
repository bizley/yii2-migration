<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBigInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBigIntTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnBigInt(['size' => 20, 'schema' => TableStructure::SCHEMA_MYSQL]);
        $this->assertEquals('$this->bigInteger(20)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength(): void
    {
        $column = new TableColumnBigInt(['size' => 20]);
        $this->assertEquals('$this->bigInteger()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneralComposite(): void
    {
        $column = new TableColumnBigInt(['size' => 20]);
        $this->assertEquals('$this->bigInteger()', $column->renderDefinition($this->getTable(true, true)));
    }

    public function testDefinitionGeneralNotPK(): void
    {
        $column = new TableColumnBigInt(['size' => 20, 'name' => 'other']);
        $this->assertEquals('$this->bigInteger()', $column->renderDefinition($this->getTable()));
    }

    public function testDefinitionGeneralPK(): void
    {
        $column = new TableColumnBigInt(['size' => 20, 'name' => 'one']);
        $this->assertEquals('$this->bigPrimaryKey()', $column->renderDefinition($this->getTable()));
    }
}
