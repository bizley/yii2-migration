<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnTime;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnTimeTest extends TableColumnTestCase
{
    public function noSchemaDataProvider(): array
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
    public function testDefinitionNoSchema(array $column, bool $generalSchema, string $result): void
    {
        $column = new TableColumnTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider(): array
    {
        return [
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->time(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', false, '$this->time(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->time(0)'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', false, '$this->time(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->time(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_PGSQL, '', true, '$this->time(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->time()'],
            [['precision' => 4], true, TableStructure::SCHEMA_PGSQL, '', true, '$this->time(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->time()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', false, '$this->time()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->time()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', false, '$this->time()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->time()'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '', true, '$this->time()'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->time()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '', true, '$this->time()'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->time(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->time(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->time(0)'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', false, '$this->time(4)'],
            [['precision' => 0], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->time(0)'],
            [['precision' => 4], false, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->time(4)'],
            [['precision' => 0], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->time()'],
            [['precision' => 4], true, TableStructure::SCHEMA_MYSQL, '5.6.4', true, '$this->time(4)'],
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
    public function testDefinitionWithSchema(
        array $column,
        bool $generalSchema,
        string $schema,
        string $version,
        bool $mapping,
        string $result
    ): void {
        $column['schema'] = $schema;
        $column['engineVersion'] = $version;
        $column['defaultMapping'] = $mapping ? 'time(0)' : null;
        $column = new TableColumnTime($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
