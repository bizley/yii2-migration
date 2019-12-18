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
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->timestamp(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->timestamp(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->timestamp(0)'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->timestamp(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->timestamp(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->timestamp(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->timestamp()'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->timestamp(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->timestamp()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->timestamp()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->timestamp()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->timestamp()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->timestamp()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->timestamp()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->timestamp()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->timestamp()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->timestamp(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->timestamp(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->timestamp(0)'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->timestamp(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->timestamp(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->timestamp(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->timestamp()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->timestamp(4)'],
        ];
    }

    /**
     * @dataProvider withSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param string $schema
     * @param string $version
     * @param bool $mapping
     * @param string $result
     */
    public function testDefinitionWithSchema($column, $generalSchema, $schema, $version, $mapping, $result)
    {
        $column['schema'] = $schema;
        $column['engineVersion'] = $version;
        $column['defaultMapping'] = $mapping ? 'timestamp(0)' : null;
        $column = new TableColumnTimestamp($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
