<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBigUPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBigUPKTest extends TableColumnTestCase
{
    public function noSchemaDataProvider(): array
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey()'],
            [['size' => 18], false, '$this->bigPrimaryKey()'],
            [['size' => 20], true, '$this->bigPrimaryKey()->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey()->unsigned()'],
            [['size' => 20], false, '$this->bigPrimaryKey()'],
            [['size' => 18], false, '$this->bigPrimaryKey()'],
            [['size' => 20], true, '$this->bigPrimaryKey()->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey()->unsigned()'],
        ];
    }

    /**
     * @dataProvider noSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionNoSchema(array $column, bool $generalSchema, string $result): void
    {
        $column = new TableColumnBigUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider(): array
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey(20)->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)->unsigned()'],
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey(20)->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)->unsigned()'],
        ];
    }

    /**
     * @dataProvider withSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithSchema(array $column, bool $generalSchema, string $result): void
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column = new TableColumnBigUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider(): array
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey()->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)->unsigned()'],
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey()->unsigned()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)->unsigned()'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchema(array $column, bool $generalSchema, string $result): void
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column = new TableColumnBigUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
