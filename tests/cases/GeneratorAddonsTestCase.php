<?php

namespace bizley\tests\cases;

use bizley\migration\Generator;
use Yii;

class GeneratorAddonsTestCase extends DbTestCase
{
    protected function getGenerator()
    {
        return new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_addons',
        ]);
    }

    public function testColumnUnique()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_unique', $table->columns);
        $this->assertEquals(true, $table->columns['col_unique']->isUnique);
    }

    public function testColumnNotNull()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_not_null', $table->columns);
        $this->assertEquals(true, $table->columns['col_not_null']->isNotNull);
    }

    public function testColumnDefaultValue()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_value', $table->columns);
        $this->assertEquals(1, $table->columns['col_default_value']->default);
    }

    public function testColumnDefaultEmptyValue()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_default_empty_value', $table->columns);
        $this->assertSame('', $table->columns['col_default_empty_value']->default);
    }
}
