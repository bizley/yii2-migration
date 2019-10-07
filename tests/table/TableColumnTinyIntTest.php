<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTinyInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTinyIntTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 3], false, '$this->tinyInteger()'],
            [['size' => 7], false, '$this->tinyInteger()'],
            [['size' => 3], true, '$this->tinyInteger()'],
            [['size' => 7], true, '$this->tinyInteger()'],
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
        $column = new TableColumnTinyInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 3], false, '$this->tinyInteger(3)'],
            [['size' => 7], false, '$this->tinyInteger(7)'],
            [['size' => 3], true, '$this->tinyInteger(3)'],
            [['size' => 7], true, '$this->tinyInteger(7)'],
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
        $column = new TableColumnTinyInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 3], false, '$this->tinyInteger(3)'],
            [['size' => 7], false, '$this->tinyInteger(7)'],
            [['size' => 3], true, '$this->tinyInteger()'],
            [['size' => 7], true, '$this->tinyInteger(7)'],
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
        $column['defaultMapping'] = 'tinyint(3)';
        $column = new TableColumnTinyInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
