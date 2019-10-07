<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnIntTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 11], false, false, '$this->integer()'],
            [['size' => 10], false, false, '$this->integer()'],
            [['size' => 11], true, false, '$this->integer()'],
            [['size' => 10], true, false, '$this->integer()'],
            [['size' => 11], false, true, '$this->integer()'],
            [['size' => 10], false, true, '$this->integer()'],
            [['size' => 11], true, true, '$this->integer()'],
            [['size' => 10], true, true, '$this->integer()'],
        ];
    }

    /**
     * @dataProvider noSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionNoSchema($column, $generalSchema, $composite, $result)
    {
        $column = new TableColumnInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 11], false, false, '$this->integer(11)'],
            [['size' => 10], false, false, '$this->integer(10)'],
            [['size' => 11], true, false, '$this->integer(11)'],
            [['size' => 10], true, false, '$this->integer(10)'],
            [['size' => 11], false, true, '$this->integer(11)'],
            [['size' => 10], false, true, '$this->integer(10)'],
            [['size' => 11], true, true, '$this->integer(11)'],
            [['size' => 10], true, true, '$this->integer(10)'],
        ];
    }

    /**
     * @dataProvider withSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithSchema($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column = new TableColumnInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 11], false, false, '$this->integer(11)'],
            [['size' => 10], false, false, '$this->integer(10)'],
            [['size' => 11], true, false, '$this->integer()'],
            [['size' => 10], true, false, '$this->integer(10)'],
            [['size' => 11], false, true, '$this->integer(11)'],
            [['size' => 10], false, true, '$this->integer(10)'],
            [['size' => 11], true, true, '$this->integer()'],
            [['size' => 10], true, true, '$this->integer(10)'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchema($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'int(11)';
        $column = new TableColumnInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withMappingAndSchemaAndPKNameDataProvider()
    {
        return [
            [['size' => 11], false, false, '$this->integer(11)->append(\'PRIMARY KEY\')'],
            [['size' => 10], false, false, '$this->integer(10)->append(\'PRIMARY KEY\')'],
            [['size' => 11], true, false, '$this->primaryKey()'],
            [['size' => 10], true, false, '$this->primaryKey(10)'],
            [['size' => 11], false, true, '$this->integer(11)'],
            [['size' => 10], false, true, '$this->integer(10)'],
            [['size' => 11], true, true, '$this->integer()'],
            [['size' => 10], true, true, '$this->integer(10)'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaAndPKNameDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchemaAndPKName($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column['name'] = 'one';
        $column = new TableColumnInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }
}
