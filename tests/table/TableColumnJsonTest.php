<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnJson;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnJsonTest extends TableColumnTestCase
{
    public function testDefinition(): void
    {
        $column = new TableColumnJson();
        $this->assertEquals('$this->json()', $column->renderDefinition($this->getTable()));
    }
}
