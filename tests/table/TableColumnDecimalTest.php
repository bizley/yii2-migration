<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDecimal;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDecimalTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['precision' => 11, 'scale' => 7], false, '$this->decimal()'],
            [['precision' => 11, 'scale' => 7], true, '$this->decimal()'],
            [['precision' => 10, 'scale' => 0], false, '$this->decimal()'],
            [['precision' => 10, 'scale' => 0], true, '$this->decimal()'],
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
        $column = new TableColumnDecimal($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['precision' => 11, 'scale' => 7], false, '$this->decimal(11, 7)'],
            [['precision' => 11, 'scale' => 7], true, '$this->decimal(11, 7)'],
            [['precision' => 10, 'scale' => 0], false, '$this->decimal(10, 0)'],
            [['precision' => 10, 'scale' => 0], true, '$this->decimal(10, 0)'],
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
        $column = new TableColumnDecimal($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['precision' => 11, 'scale' => 7], false, '$this->decimal(11, 7)'],
            [['precision' => 11, 'scale' => 7], true, '$this->decimal(11, 7)'],
            [['precision' => 10, 'scale' => 0], false, '$this->decimal(10, 0)'],
            [['precision' => 10, 'scale' => 0], true, '$this->decimal()'],
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
        $column['defaultMapping'] = 'decimal(10,0)';
        $column = new TableColumnDecimal($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
