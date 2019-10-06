<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnDoubleTest extends TableColumnTestCase
{
    public function noSchemaDataProvider(): array
    {
        return [
            [['precision' => 11], false, '$this->double()'],
            [['precision' => 11], true, '$this->double()'],
            [['precision' => 15], false, '$this->double()'],
            [['precision' => 15], true, '$this->double()'],
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
        $column = new TableColumnDouble($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider(): array
    {
        return [
            [['precision' => 11], false, '$this->double(11)'],
            [['precision' => 11], true, '$this->double(11)'],
            [['precision' => 15], false, '$this->double(15)'],
            [['precision' => 15], true, '$this->double(15)'],
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
        $column['schema'] = TableStructure::SCHEMA_CUBRID;
        $column = new TableColumnDouble($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider(): array
    {
        return [
            [['precision' => 11], false, '$this->double(11)'],
            [['precision' => 11], true, '$this->double(11)'],
            [['precision' => 15], false, '$this->double(15)'],
            [['precision' => 15], true, '$this->double()'],
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
        $column['schema'] = TableStructure::SCHEMA_CUBRID;
        $column['defaultMapping'] = 'double(15)';
        $column = new TableColumnDouble($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
