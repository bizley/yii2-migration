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
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->dateTime(4)'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->dateTime(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->dateTime(0)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->dateTime(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->dateTime(4)'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->dateTime(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->dateTime(0)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->dateTime()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->dateTime()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->dateTime()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->dateTime()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->dateTime()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->dateTime()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->dateTime()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->dateTime()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->dateTime()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->dateTime(4)'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->dateTime(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->dateTime(0)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->dateTime(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->dateTime(4)'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->dateTime(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->dateTime(0)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->dateTime()'],
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
        $column['defaultMapping'] = $mapping ? 'datetime(0)' : null;
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
