<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTime;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTimeTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->time()'],
            [['precision' => 4], false, '$this->time()'],
            [['precision' => 0], true, '$this->time()'],
            [['precision' => 4], true, '$this->time()'],
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
        $column = new TableColumnTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->time(0)'],
            [['precision' => 4], false, '$this->time(4)'],
            [['precision' => 0], true, '$this->time(0)'],
            [['precision' => 4], true, '$this->time(4)'],
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
        $column = new TableColumnTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->time(0)'],
            [['precision' => 4], false, '$this->time(4)'],
            [['precision' => 0], true, '$this->time()'],
            [['precision' => 4], true, '$this->time(4)'],
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
        $column['defaultMapping'] = 'time(0)';
        $column = new TableColumnTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
