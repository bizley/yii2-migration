<?php

declare(strict_types=1);

namespace bizley\tests\mysql;

use bizley\tests\cases\GeneratorTestCase;

/**
 * @group mysql
 */
class GeneratorTest extends GeneratorTestCase
{
    public static $schema = 'mysql';

    public function testSchema(): void
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals('mysql', $table->schema);
    }

    public function testPrimaryKeyNonComposite(): void
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals(['id'], $table->primaryKey->columns);
        $this->assertEquals(null, $table->primaryKey->name);
        $this->assertFalse($table->primaryKey->isComposite());
    }

    public function testPrimaryKeyComposite(): void
    {
        $table = $this->getGenerator('test_pk_composite')->table;
        $this->assertEquals(['one', 'two'], $table->primaryKey->columns);
        $this->assertEquals(null, $table->primaryKey->name);
        $this->assertTrue($table->primaryKey->isComposite());
    }

    public function testForeignKey(): void
    {
        $table = $this->getGenerator('test_fk')->table;
        $this->assertArrayHasKey('fk-test_fk-pk_id', $table->foreignKeys);
        $this->assertEquals(['pk_id'], $table->foreignKeys['fk-test_fk-pk_id']->columns);
        $this->assertEquals('test_pk', $table->foreignKeys['fk-test_fk-pk_id']->refTable);
        $this->assertEquals(['id'], $table->foreignKeys['fk-test_fk-pk_id']->refColumns);
        $this->assertEquals('fk-test_fk-pk_id', $table->foreignKeys['fk-test_fk-pk_id']->name);
    }
}
