<?php

namespace bizley\tests\mysql;

use yii\db\Expression;

/**
 * @group mysql
 */
class GeneratorAddonsTest extends \bizley\tests\cases\GeneratorAddonsTestCase
{
    public static $schema = 'mysql';

    public function testColumnUnsigned()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_unsigned', $table->columns);
        $this->assertEquals(true, $table->columns['col_unsigned']->isUnsigned);
    }

    public function testColumnDefaultExpression()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_expression', $table->columns);
        $this->assertEquals(new Expression('CURRENT_TIMESTAMP'), $table->columns['col_default_expression']->default);
    }

    public function testColumnComment()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_comment', $table->columns);
        $this->assertEquals('comment', $table->columns['col_comment']->comment);
    }
}
