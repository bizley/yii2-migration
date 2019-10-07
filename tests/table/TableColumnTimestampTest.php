<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTimestamp;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTimestampTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->timestamp()'],
            [['precision' => 4], false, '$this->timestamp()'],
            [['precision' => 0], true, '$this->timestamp()'],
            [['precision' => 4], true, '$this->timestamp()'],
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
        $column = new TableColumnTimestamp($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->timestamp(0)'],
            [['precision' => 4], false, '$this->timestamp(4)'],
            [['precision' => 0], true, '$this->timestamp(0)'],
            [['precision' => 4], true, '$this->timestamp(4)'],
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
        $column = new TableColumnTimestamp($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['precision' => 0], false, '$this->timestamp(0)'],
            [['precision' => 4], false, '$this->timestamp(4)'],
            [['precision' => 0], true, '$this->timestamp()'],
            [['precision' => 4], true, '$this->timestamp(4)'],
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
        $column = new TableColumnTimestamp($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
