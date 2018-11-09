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
}
