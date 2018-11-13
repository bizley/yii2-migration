<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDoubleTest extends TableColumnTestCase
{
    public function testDefinitionSpecific(): void
    {
        $column = new TableColumnDouble(['precision' => 4, 'schema' => TableStructure::SCHEMA_CUBRID]);
        $this->assertEquals('$this->double(4)', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionSpecificNoLength(): void
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double()', $column->renderDefinition($this->getTable(false)));
    }

    public function testDefinitionGeneral(): void
    {
        $column = new TableColumnDouble(['precision' => 4]);
        $this->assertEquals('$this->double()', $column->renderDefinition($this->getTable()));
    }
}
