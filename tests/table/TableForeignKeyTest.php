<?php declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableForeignKey;
use bizley\migration\table\TableStructure;

class TableForeignKeyTest extends \PHPUnit\Framework\TestCase
{
    public function testNameGenerated(): void
    {
        $fk = new TableForeignKey(['columns' => ['one', 'two']]);
        $this->assertEquals('fk-table-one-two', $fk->renderName(new TableStructure(['name' => 'table'])));
    }

    public function testRenderRefTableUsePrefix(): void
    {
        $fk = new TableForeignKey(['refTable' => 'test']);
        $this->assertEquals('{{%test}}', $fk->renderRefTableName(new TableStructure()));
    }

    public function testRenderRefTableDontUsePrefix(): void
    {
        $fk = new TableForeignKey(['refTable' => 'test']);
        $this->assertEquals('test', $fk->renderRefTableName(new TableStructure(['usePrefix' => false])));
    }

    public function testRenderRefTableDbPrefix(): void
    {
        $fk = new TableForeignKey(['refTable' => 'prefix_test']);
        $this->assertEquals('{{%test}}', $fk->renderRefTableName(new TableStructure(['dbPrefix' => 'prefix_'])));
    }

    public function testRender(): void
    {
        $fk = new TableForeignKey([
            'name' => 'fk_test',
            'columns' => ['fk_column'],
            'refTable' => 'ref_table',
            'refColumns' => ['ref_id'],
            'onDelete' => 'RESTRICT',
            'onUpdate' => 'CASCADE',
        ]);
        $this->assertEquals('$this->addForeignKey(\'fk_test\', \'{{%table}}\', \'fk_column\', \'{{%ref_table}}\', \'ref_id\', \'RESTRICT\', \'CASCADE\');',
            $fk->render(new TableStructure(['name' => 'table']), 0));
    }
}
