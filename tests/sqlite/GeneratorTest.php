<?php

declare(strict_types=1);

namespace bizley\tests\sqlite;

use bizley\tests\cases\GeneratorTestCase;

/**
 * @group sqlite
 */
class GeneratorTest extends GeneratorTestCase
{
    public static $schema = 'sqlite';

    public function testSchema(): void
    {
        $table = $this->getGenerator('test_pk')->table;
        $this->assertEquals('sqlite', $table->schema);
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
}
