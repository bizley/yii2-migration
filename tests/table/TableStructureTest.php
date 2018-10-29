<?php

namespace bizley\tests\table;

use bizley\migration\table\TableStructure;

class TableStructureTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderNameNoPrefix()
    {
        $table = new TableStructure([
            'name' => 'test',
            'usePrefix' => false,
        ]);
        $this->assertEquals('test', $table->renderName());
    }

    public function testRenderNameWithPrefix()
    {
        $table = new TableStructure([
            'name' => 'test',
        ]);
        $this->assertEquals('{{%test}}', $table->renderName());
    }

    public function testRenderNameWithPrefixAlreadyInName()
    {
        $table = new TableStructure([
            'name' => 'prefix_test',
            'dbPrefix' => 'prefix_',
        ]);
        $this->assertEquals('{{%test}}', $table->renderName());
    }

    public function testRenderTableCreate()
    {
        $table = new TableStructure([
            'name' => 'test',
        ]);
        $this->assertEquals("        \$this->createTable('{{%test}}', [\n        ]);\n", $table->renderTable());
    }

    public function testRenderTableCreateDefaultTableOptions()
    {
        $table = new TableStructure([
            'name' => 'test',
            'tableOptionsInit' => '$tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }',
            'tableOptions' => '$tableOptions',
        ]);
        $this->assertEquals("        \$tableOptions = null;\n        if (\$this->db->driverName === 'mysql') {\n            \$tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';\n        }\n\n        \$this->createTable('{{%test}}', [\n        ], \$tableOptions);\n", $table->renderTable());
    }
}
