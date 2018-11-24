<?php

namespace bizley\tests\cases;

use bizley\migration\Generator;
use Yii;

class GeneratorTestCase extends DbTestCase
{
    protected function getGenerator($tableName)
    {
        return new Generator([
            'db' => Yii::$app->db,
            'tableName' => $tableName,
        ]);
    }

    public function testIndexSingle()
    {
        if (!method_exists(Yii::$app->db->schema, 'getTableIndexes')) {
            $this->markTestSkipped('Non-unique indexes are tracked since Yii 2.0.13.');
        }

        $table = $this->getGenerator('test_index_single')->table;
        $this->assertArrayHasKey('idx-test_index_single-col', $table->indexes);
        $this->assertEquals(['col'], $table->indexes['idx-test_index_single-col']->columns);
        $this->assertEquals('idx-test_index_single-col', $table->indexes['idx-test_index_single-col']->name);
        $this->assertFalse($table->indexes['idx-test_index_single-col']->unique);
    }

    public function testIndexUnique()
    {
        $table = $this->getGenerator('test_index_unique')->table;
        $this->assertArrayHasKey('idx-test_index_unique-col', $table->indexes);
        $this->assertEquals(['col'], $table->indexes['idx-test_index_unique-col']->columns);
        $this->assertEquals('idx-test_index_unique-col', $table->indexes['idx-test_index_unique-col']->name);
        $this->assertTrue($table->indexes['idx-test_index_unique-col']->unique);
    }

    public function testIndexMulti()
    {
        if (!method_exists(Yii::$app->db->schema, 'getTableIndexes')) {
            $this->markTestSkipped('Non-unique indexes are tracked since Yii 2.0.13.');
        }

        $table = $this->getGenerator('test_index_multi')->table;
        $this->assertArrayHasKey('idx-test_index_multi-cols', $table->indexes);
        $this->assertEquals(['one', 'two'], $table->indexes['idx-test_index_multi-cols']->columns);
        $this->assertEquals('idx-test_index_multi-cols', $table->indexes['idx-test_index_multi-cols']->name);
        $this->assertFalse($table->indexes['idx-test_index_multi-cols']->unique);
    }
}
