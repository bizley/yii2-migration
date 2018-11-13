<?php declare(strict_types=1);

namespace bizley\tests\pgsql;

use yii\db\Expression;

/**
 * @group pgsql
 */
class GeneratorAddonsTest extends \bizley\tests\cases\GeneratorAddonsTestCase
{
    public static $schema = 'pgsql';

    public function testColumnDefaultExpression(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_expression', $table->columns);
        $this->assertEquals(new Expression('now()'), $table->columns['col_default_expression']->default);
    }

    public function testColumnComment(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_comment', $table->columns);
        $this->assertEquals('comment', $table->columns['col_comment']->comment);
    }
}
