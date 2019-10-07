<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnPKTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 11], false, '$this->primaryKey()'],
            [['size' => 12], false, '$this->primaryKey()'],
            [['size' => 11], true, '$this->primaryKey()'],
            [['size' => 12], true, '$this->primaryKey()'],
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
        $column = new TableColumnPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 11], false, '$this->primaryKey(11)'],
            [['size' => 12], false, '$this->primaryKey(12)'],
            [['size' => 11], true, '$this->primaryKey(11)'],
            [['size' => 12], true, '$this->primaryKey(12)'],
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
        $column = new TableColumnPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 11], false, '$this->primaryKey(11)'],
            [['size' => 12], false, '$this->primaryKey(12)'],
            [['size' => 11], true, '$this->primaryKey()'],
            [['size' => 12], true, '$this->primaryKey(12)'],
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
        $column['defaultMapping'] = 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column = new TableColumnPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
