<?php

declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\migration\table\TablePrimaryKey;
use bizley\migration\table\TableStructure;
use PHPUnit\Framework\TestCase;

class TableColumnTestCase extends TestCase
{
    public function getTable($generalSchema = true, $composite = false, $schema = null): TableStructure
    {
        return new TableStructure([
            'name' => 'table',
            'generalSchema' => $generalSchema,
            'primaryKey' => new TablePrimaryKey([
                'columns' => $composite ? ['one', 'two'] : ['one'],
            ]),
            'schema' => $schema,
        ]);
    }
}
