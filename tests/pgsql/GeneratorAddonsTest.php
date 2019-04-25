<?php

namespace bizley\tests\pgsql;

use bizley\tests\cases\GeneratorAddonsTestCase;
use yii\db\Expression;

/**
 * @group pgsql
 */
class GeneratorAddonsTest extends GeneratorAddonsTestCase
{
    public static $schema = 'pgsql';

    public function testColumnDefaultExpression()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_expression', $table->columns);
        $this->assertEquals(new Expression('now()'), $table->columns['col_default_expression']->default);
    }

    public function testColumnComment()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_comment', $table->columns);
        $this->assertEquals('comment', $table->columns['col_comment']->comment);
    }

    public function testColumnDefaultArrayValue(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_array', $table->columns);
        $this->assertEquals([1, 2, 3], $table->columns['col_default_array']->default);
    }
}
