<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnUPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnUPKTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 10], false, '$this->primaryKey()'],
            [['size' => 8], false, '$this->primaryKey()'],
            [['size' => 10], true, '$this->primaryKey()->unsigned()'],
            [['size' => 8], true, '$this->primaryKey()->unsigned()'],
            [['size' => 10], false, '$this->primaryKey()'],
            [['size' => 8], false, '$this->primaryKey()'],
            [['size' => 10], true, '$this->primaryKey()->unsigned()'],
            [['size' => 8], true, '$this->primaryKey()->unsigned()'],
        ];
    }

    /**
     * @dataProvider noSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionNoSchema($column, $generalSchema, $result)
    {
        $column = new TableColumnUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 10], false, '$this->primaryKey(10)'],
            [['size' => 8], false, '$this->primaryKey(8)'],
            [['size' => 10], true, '$this->primaryKey(10)->unsigned()'],
            [['size' => 8], true, '$this->primaryKey(8)->unsigned()'],
            [['size' => 10], false, '$this->primaryKey(10)'],
            [['size' => 8], false, '$this->primaryKey(8)'],
            [['size' => 10], true, '$this->primaryKey(10)->unsigned()'],
            [['size' => 8], true, '$this->primaryKey(8)->unsigned()'],
        ];
    }

    /**
     * @dataProvider withSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithSchema($column, $generalSchema, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column = new TableColumnUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 10], false, '$this->primaryKey(10)'],
            [['size' => 8], false, '$this->primaryKey(8)'],
            [['size' => 10], true, '$this->primaryKey()->unsigned()'],
            [['size' => 8], true, '$this->primaryKey(8)->unsigned()'],
            [['size' => 10], false, '$this->primaryKey(10)'],
            [['size' => 8], false, '$this->primaryKey(8)'],
            [['size' => 10], true, '$this->primaryKey()->unsigned()'],
            [['size' => 8], true, '$this->primaryKey(8)->unsigned()'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchema($column, $generalSchema, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column = new TableColumnUPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
