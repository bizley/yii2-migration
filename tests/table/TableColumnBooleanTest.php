<?php

declare(strict_types=1);

namespace bizley\migration\tests\table;

use bizley\migration\table\TableColumnBoolean;

class TableColumnBooleanTest extends TableColumnTestCase
{
    public function testDefinition(): void
    {
        $column = new TableColumnBoolean();
        $this->assertEquals('$this->boolean()', $column->renderDefinition($this->getTable()));
    }
}
