<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDateTime;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDateTimeTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['precision' => 4], false, '$this->dateTime()'],
            [['precision' => 4], true, '$this->dateTime()'],
            [['precision' => 0], false, '$this->dateTime()'],
            [['precision' => 0], true, '$this->dateTime()'],
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
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['precision' => 4], false, '$this->dateTime(4)'],
            [['precision' => 4], true, '$this->dateTime(4)'],
            [['precision' => 0], false, '$this->dateTime(0)'],
            [['precision' => 0], true, '$this->dateTime(0)'],
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
        $column['schema'] = TableStructure::SCHEMA_PGSQL;
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['precision' => 4], false, '$this->dateTime(4)'],
            [['precision' => 4], true, '$this->dateTime(4)'],
            [['precision' => 0], false, '$this->dateTime(0)'],
            [['precision' => 0], true, '$this->dateTime()'],
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
        $column['schema'] = TableStructure::SCHEMA_PGSQL;
        $column['defaultMapping'] = 'timestamp(0)';
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
