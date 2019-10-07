<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnFloat;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnFloatTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['precision' => 9], false, '$this->float()'],
            [['precision' => 9], true, '$this->float()'],
            [['precision' => 7], false, '$this->float()'],
            [['precision' => 7], true, '$this->float()'],
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
        $column = new TableColumnFloat($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['precision' => 9], false, '$this->float(9)'],
            [['precision' => 9], true, '$this->float(9)'],
            [['precision' => 7], false, '$this->float(7)'],
            [['precision' => 7], true, '$this->float(7)'],
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
        $column['schema'] = TableStructure::SCHEMA_CUBRID;
        $column = new TableColumnFloat($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['precision' => 9], false, '$this->float(9)'],
            [['precision' => 9], true, '$this->float(9)'],
            [['precision' => 7], false, '$this->float(7)'],
            [['precision' => 7], true, '$this->float()'],
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
        $column['schema'] = TableStructure::SCHEMA_CUBRID;
        $column['defaultMapping'] = 'float(7)';
        $column = new TableColumnFloat($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema)));
    }
}
