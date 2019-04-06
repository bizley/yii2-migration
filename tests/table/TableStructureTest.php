<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableStructure;
use PHPUnit\Framework\TestCase;

class TableStructureTest extends TestCase
{
    public function testRenderNameNoPrefix(): void
    {
        $table = new TableStructure([
            'name' => 'test',
            'usePrefix' => false,
        ]);
        $this->assertEquals('test', $table->renderName());
    }

    public function testRenderNameWithPrefix(): void
    {
        $table = new TableStructure([
            'name' => 'test',
        ]);
        $this->assertEquals('{{%test}}', $table->renderName());
    }

    public function testRenderNameWithPrefixAlreadyInName(): void
    {
        $table = new TableStructure([
            'name' => 'prefix_test',
            'dbPrefix' => 'prefix_',
        ]);
        $this->assertEquals('{{%test}}', $table->renderName());
    }

    public function testRenderTableCreate(): void
    {
        $table = new TableStructure([
            'name' => 'test',
        ]);
        $this->assertEquals("        \$this->createTable('{{%test}}', [\n        ]);\n", $table->renderTable());
    }

    public function testRenderTableCreateDefaultTableOptions(): void
    {
        $table = new TableStructure([
            'name' => 'test',
            'tableOptionsInit' => '$tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }',
            'tableOptions' => '$tableOptions',
        ]);
        $this->assertEquals(
            "        \$tableOptions = null;\n        if (\$this->db->driverName === 'mysql') {\n            \$tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';\n        }\n\n        \$this->createTable('{{%test}}', [\n        ], \$tableOptions);\n",
            $table->renderTable()
        );
    }
}
