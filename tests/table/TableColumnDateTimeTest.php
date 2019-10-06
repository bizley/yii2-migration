<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDateTime;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDateTimeTest extends TableColumnTestCase
{
    public function noSchemaDataProvider(): array
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
    public function testDefinitionNoSchema(array $column, bool $generalSchema, string $result): void
    {
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider(): array
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
    public function testDefinitionWithSchema(array $column, bool $generalSchema, string $result): void
    {
        $column['schema'] = TableStructure::SCHEMA_PGSQL;
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider(): array
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
    public function testDefinitionWithMappingAndSchema(array $column, bool $generalSchema, string $result): void
    {
        $column['schema'] = TableStructure::SCHEMA_PGSQL;
        $column['defaultMapping'] = 'timestamp(0)';
        $column = new TableColumnDateTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
