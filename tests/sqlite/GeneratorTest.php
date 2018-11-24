<?php

namespace bizley\tests\sqlite;

/**
 * @group sqlite
 */
class GeneratorTest extends \bizley\tests\cases\GeneratorTestCase
{
    public static $schema = 'sqlite';

    public function testSchema()
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals('sqlite', $table->schema);
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
}
