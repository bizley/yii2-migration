<?php declare(strict_types=1);

namespace bizley\tests\mysql;

/**
 * @group mysql
 */
class GeneratorAddonsTest extends \bizley\tests\cases\GeneratorAddonsTestCase
{
    public static $schema = 'mysql';

    public function testColumnUnsigned(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_unsigned', $table->columns);
        $this->assertEquals(true, $table->columns['col_unsigned']->isUnsigned);
    }
}
