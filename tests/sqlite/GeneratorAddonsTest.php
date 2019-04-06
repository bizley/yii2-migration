<?php

namespace bizley\tests\sqlite;

use bizley\tests\cases\GeneratorAddonsTestCase;

/**
 * @group sqlite
 */
class GeneratorAddonsTest extends GeneratorAddonsTestCase
{
    public static $schema = 'sqlite';

    public function testColumnUnsigned()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_unsigned', $table->columns);
        $this->assertEquals(true, $table->columns['col_unsigned']->isUnsigned);
    }
}
