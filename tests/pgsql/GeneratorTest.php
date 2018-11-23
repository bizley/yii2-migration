<?php declare(strict_types=1);

namespace bizley\tests\pgsql;

/**
 * @group pgsql
 */
class GeneratorTest extends \bizley\tests\cases\GeneratorTestCase
{
    public static $schema = 'pgsql';

    public function testSchema(): void
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals('pgsql', $table->schema);
    }

    public function testPrimaryKeyNonComposite(): void
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals(['id'], $table->primaryKey->columns);
        $this->assertEquals('test_pk_pkey', $table->primaryKey->name);
        $this->assertFalse($table->primaryKey->isComposite());
    }

    public function testPrimaryKeyComposite(): void
    {
        $table = $this->getGenerator('test_pk_composite')->table;
        $this->assertEquals(['one', 'two'], $table->primaryKey->columns);
        $this->assertEquals('PRIMARYKEY', $table->primaryKey->name);
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
