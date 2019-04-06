<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDate;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDateTest extends TableColumnTestCase
{
    public function testDefinition(): void
    {
        $column = new TableColumnDate();
        $this->assertEquals('$this->date()', $column->renderDefinition($this->getTable()));
    }
}
