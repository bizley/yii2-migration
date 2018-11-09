<?php declare(strict_types=1);

namespace bizley\tests\pgsql;

/**
 * @group pgsql
 */
class GeneratorAddonsTest extends \bizley\tests\cases\GeneratorAddonsTestCase
{
    public static $schema = 'pgsql';

    public function testColumnUnsigned(): void
    {
        $this->markTestSkipped('PostgreSQL not supporting unsigned');
    }
}
