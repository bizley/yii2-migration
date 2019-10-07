<?php

namespace bizley\tests\table;

use bizley\migration\table\TableColumnBigInt;
use bizley\migration\table\TableStructure;
use bizley\tests\cases\TableColumnTestCase;

class TableColumnBigIntTest extends TableColumnTestCase
{
    public function noSchemaDataProvider()
    {
        return [
            [['size' => 20], false, false, '$this->bigInteger()'],
            [['size' => 18], false, false, '$this->bigInteger()'],
            [['size' => 20], true, false, '$this->bigInteger()'],
            [['size' => 18], true, false, '$this->bigInteger()'],
            [['size' => 20], false, true, '$this->bigInteger()'],
            [['size' => 18], false, true, '$this->bigInteger()'],
            [['size' => 20], true, true, '$this->bigInteger()'],
            [['size' => 18], true, true, '$this->bigInteger()'],
        ];
    }

    /**
     * @dataProvider noSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionNoSchema($column, $generalSchema, $composite, $result)
    {
        $column = new TableColumnBigInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withSchemaDataProvider()
    {
        return [
            [['size' => 20], false, false, '$this->bigInteger(20)'],
            [['size' => 18], false, false, '$this->bigInteger(18)'],
            [['size' => 20], true, false, '$this->bigInteger(20)'],
            [['size' => 18], true, false, '$this->bigInteger(18)'],
            [['size' => 20], false, true, '$this->bigInteger(20)'],
            [['size' => 18], false, true, '$this->bigInteger(18)'],
            [['size' => 20], true, true, '$this->bigInteger(20)'],
            [['size' => 18], true, true, '$this->bigInteger(18)'],
        ];
    }

    /**
     * @dataProvider withSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithSchema($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column = new TableColumnBigInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withMappingAndSchemaDataProvider()
    {
        return [
            [['size' => 20], false, false, '$this->bigInteger(20)'],
            [['size' => 18], false, false, '$this->bigInteger(18)'],
            [['size' => 20], true, false, '$this->bigInteger()'],
            [['size' => 18], true, false, '$this->bigInteger(18)'],
            [['size' => 20], false, true, '$this->bigInteger(20)'],
            [['size' => 18], false, true, '$this->bigInteger(18)'],
            [['size' => 20], true, true, '$this->bigInteger()'],
            [['size' => 18], true, true, '$this->bigInteger(18)'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchema($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'bigint(20)';
        $column = new TableColumnBigInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }

    public function withMappingAndSchemaAndPKNameDataProvider()
    {
        return [
            [['size' => 20], false, false, '$this->bigInteger(20)->append(\'PRIMARY KEY\')'],
            [['size' => 18], false, false, '$this->bigInteger(18)->append(\'PRIMARY KEY\')'],
            [['size' => 20], true, false, '$this->bigPrimaryKey()'],
            [['size' => 18], true, false, '$this->bigPrimaryKey(18)'],
            [['size' => 20], false, true, '$this->bigInteger(20)'],
            [['size' => 18], false, true, '$this->bigInteger(18)'],
            [['size' => 20], true, true, '$this->bigInteger()'],
            [['size' => 18], true, true, '$this->bigInteger(18)'],
        ];
    }

    /**
     * @dataProvider withMappingAndSchemaAndPKNameDataProvider
     * @param array $column
     * @param bool $generalSchema
     * @param bool $composite
     * @param string $result
     */
    public function testDefinitionWithMappingAndSchemaAndPKName($column, $generalSchema, $composite, $result)
    {
        $column['schema'] = TableStructure::SCHEMA_MYSQL;
        $column['defaultMapping'] = 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $column['name'] = 'one';
        $column = new TableColumnBigInt($column);
        $this->assertEquals($result, $column->renderDefinition($this->getTable($generalSchema, $composite)));
    }
}
