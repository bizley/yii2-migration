<?php

namespace bizley\migration\tests;

use bizley\migration\Generator;
use Yii;

class GeneratorTest extends TestCase
{
    protected function getGenerator($tableName, $generalSchema = true, $useTablePrefix = true, $namespace = null)
    {
        return new Generator([
            'db' => Yii::$app->db,
//            'view' => Yii::$app->view,
//            'templateFile' => '@bizley/migration/views/create_migration.php',
//            'className' => 'm990101_000000_create_table_' . $tableName,
            'tableName' => $tableName,
//            'useTablePrefix' => $useTablePrefix,
//            'namespace' => $namespace,
//            'generalSchema' => $generalSchema,
        ]);
    }

    public function testSchema()
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals('mysql', $table->schema);
    }

    public function testPrimaryKeyNonComposite()
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals(['id'], $table->primaryKey->columns);
        $this->assertEquals(null, $table->primaryKey->name);
        $this->assertFalse($table->primaryKey->isComposite());
    }

    public function testPrimaryKeyComposite()
    {
        $table = $this->getGenerator('test_pk_composite')->table;
        $this->assertEquals(['one', 'two'], $table->primaryKey->columns);
        $this->assertEquals(null, $table->primaryKey->name);
        $this->assertTrue($table->primaryKey->isComposite());
    }

    public function testIndexSingle()
    {
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
        $table = $this->getGenerator('test_index_multi')->table;
        $this->assertArrayHasKey('idx-test_index_multi-cols', $table->indexes);
        $this->assertEquals(['one', 'two'], $table->indexes['idx-test_index_multi-cols']->columns);
        $this->assertEquals('idx-test_index_multi-cols', $table->indexes['idx-test_index_multi-cols']->name);
        $this->assertFalse($table->indexes['idx-test_index_multi-cols']->unique);
    }
}
