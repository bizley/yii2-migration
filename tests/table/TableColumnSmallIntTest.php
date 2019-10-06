<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnSmallInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnSmallIntTest extends TableColumnTestCase
{
    public function noSchemaDataProvider(): array
    {
        return [
            [['size' => 6], false, '$this->smallInteger()'],
            [['size' => 7], false, '$this->smallInteger()'],
            [['size' => 6], true, '$this->smallInteger()'],
            [['size' => 7], true, '$this->smallInteger()'],
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
        $column = new TableColumnSmallInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider(): array
    {
        return [
            [['size' => 6], false, '$this->smallInteger(6)'],
            [['size' => 7], false, '$this->smallInteger(7)'],
            [['size' => 6], true, '$this->smallInteger(6)'],
            [['size' => 7], true, '$this->smallInteger(7)'],
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
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column = new TableColumnSmallInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider(): array
    {
        return [
            [['size' => 6], false, '$this->smallInteger(6)'],
            [['size' => 7], false, '$this->smallInteger(7)'],
            [['size' => 6], true, '$this->smallInteger()'],
            [['size' => 7], true, '$this->smallInteger(7)'],
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
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'smallint(6)';
        $column = new TableColumnSmallInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
