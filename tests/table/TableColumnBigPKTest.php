<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBigPK;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBigPKTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey()'],
            [['size' => 18], false, '$this->bigPrimaryKey()'],
            [['size' => 20], true, '$this->bigPrimaryKey()'],
            [['size' => 18], true, '$this->bigPrimaryKey()'],
            [['size' => 20], false, '$this->bigPrimaryKey()'],
            [['size' => 18], false, '$this->bigPrimaryKey()'],
            [['size' => 20], true, '$this->bigPrimaryKey()'],
            [['size' => 18], true, '$this->bigPrimaryKey()'],
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
        $column = new TableColumnBigPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey(20)'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)'],
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey(20)'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)'],
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
        $column = new TableColumnBigPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)'],
            [['size' => 20], false, '$this->bigPrimaryKey(20)'],
            [['size' => 18], false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], true, '$this->bigPrimaryKey()'],
            [['size' => 18], true, '$this->bigPrimaryKey(18)'],
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
        $column['defaultMapping'] = 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column = new TableColumnBigPK($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
