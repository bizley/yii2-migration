<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\SqlColumnMapper;
use PHPUnit\Framework\TestCase;
use yii\db\cubrid\Schema as CubridSchema;
use yii\db\mssql\Schema as MSSqlSchema;
use yii\db\mysql\Schema as MySqlSchema;

/** @group sqlcolumnmapper */
class SqlColumnMapperTest extends TestCase
{
    public function providerForCubrid(): array
    {
        return [
            ['short', ['type' => 'smallint']],
            ['smallint', ['type' => 'smallint']],
            ['int', ['type' => 'integer']],
            ['integer', ['type' => 'integer']],
            ['bigint', ['type' => 'bigint']],
            ['numeric', ['type' => 'decimal']],
            ['decimal', ['type' => 'decimal']],
            ['float', ['type' => 'float']],
            ['real', ['type' => 'float']],
            ['double', ['type' => 'double']],
            ['double precision', ['type' => 'double']],
            ['monetary', ['type' => 'money']],
            ['date', ['type' => 'date']],
            ['time', ['type' => 'time']],
            ['timestamp', ['type' => 'timestamp']],
            ['datetime', ['type' => 'datetime']],
            ['char', ['type' => 'char']],
            ['varchar', ['type' => 'string']],
            ['char varying', ['type' => 'string']],
            ['nchar', ['type' => 'char']],
            ['nchar varying', ['type' => 'string']],
            ['string', ['type' => 'string']],
            ['blob', ['type' => 'binary']],
            ['clob', ['type' => 'binary']],
            ['bit', ['type' => 'integer']],
            ['bit varying', ['type' => 'integer']],
            ['set', ['type' => 'string']],
            ['multiset', ['type' => 'string']],
            ['list', ['type' => 'string']],
            ['sequence', ['type' => 'string']],
            ['enum', ['type' => 'string']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForCubrid
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectCubridTypes(string $definition, array $schema): void
    {
        $this->assertSame($schema, SqlColumnMapper::map($definition, (new CubridSchema())->typeMap));
    }

    public function providerForMSSql(): array
    {
        return [
            ['bigint', ['type' => 'bigint']],
            ['numeric', ['type' => 'decimal']],
            ['bit', ['type' => 'smallint']],
            ['smallint', ['type' => 'smallint']],
            ['decimal', ['type' => 'decimal']],
            ['smallmoney', ['type' => 'money']],
            ['int', ['type' => 'integer']],
            ['tinyint', ['type' => 'tinyint']],
            ['money', ['type' => 'money']],
            ['float', ['type' => 'float']],
            ['double', ['type' => 'double']],
            ['real', ['type' => 'float']],
            ['date', ['type' => 'date']],
            ['datetimeoffset', ['type' => 'datetime']],
            ['datetime2', ['type' => 'datetime']],
            ['smalldatetime', ['type' => 'datetime']],
            ['datetime', ['type' => 'datetime']],
            ['time', ['type' => 'time']],
            ['char', ['type' => 'char']],
            ['varchar', ['type' => 'string']],
            ['text', ['type' => 'text']],
            ['nchar', ['type' => 'char']],
            ['nvarchar', ['type' => 'string']],
            ['ntext', ['type' => 'text']],
            ['binary', ['type' => 'binary']],
            ['varbinary', ['type' => 'binary']],
            ['image', ['type' => 'binary']],
            ['timestamp', ['type' => 'timestamp']],
            ['hierarchyid', ['type' => 'string']],
            ['uniqueidentifier', ['type' => 'string']],
            ['sql_variant', ['type' => 'string']],
            ['xml', ['type' => 'string']],
            ['table', ['type' => 'string']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForMSSql
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectMSSqlTypes(string $definition, array $schema): void
    {
        $this->assertSame($schema, SqlColumnMapper::map($definition, (new MSSqlSchema())->typeMap));
    }

    public function providerForMySql(): array
    {
        return [
            ['tinyint', ['type' => 'tinyint']],
            ['bit', ['type' => 'integer']],
            ['smallint', ['type' => 'smallint']],
            ['mediumint', ['type' => 'integer']],
            ['int', ['type' => 'integer']],
            ['integer', ['type' => 'integer']],
            ['bigint', ['type' => 'bigint']],
            ['float', ['type' => 'float']],
            ['double', ['type' => 'double']],
            ['real', ['type' => 'float']],
            ['decimal', ['type' => 'decimal']],
            ['numeric', ['type' => 'decimal']],
            ['tinytext', ['type' => 'text']],
            ['mediumtext', ['type' => 'text']],
            ['longtext', ['type' => 'text']],
            ['longblob', ['type' => 'binary']],
            ['blob', ['type' => 'binary']],
            ['text', ['type' => 'text']],
            ['varchar', ['type' => 'string']],
            ['string', ['type' => 'string']],
            ['char', ['type' => 'char']],
            ['datetime', ['type' => 'datetime']],
            ['year', ['type' => 'date']],
            ['date', ['type' => 'date']],
            ['time', ['type' => 'time']],
            ['timestamp', ['type' => 'timestamp']],
            ['enum', ['type' => 'string']],
            ['varbinary', ['type' => 'binary']],
            ['json', ['type' => 'json']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForMySql
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectMySqlTypes(string $definition, array $schema): void
    {
        $this->assertSame($schema, SqlColumnMapper::map($definition, (new MySqlSchema())->typeMap));
    }

    public function providerForOracle(): array
    {
        return [
            ['float', ['type' => 'double']],
            ['double', ['type' => 'double']],
            ['number', ['type' => 'decimal']],
            ['integer', ['type' => 'integer']],
            ['blob', ['type' => 'binary']],
            ['clob', ['type' => 'text']],
            ['timestamp', ['type' => 'timestamp']],
            ['string', ['type' => 'string']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForOracle
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectOracleTypes(string $definition, array $schema): void
    {
        $this->assertSame(
            $schema,
            SqlColumnMapper::map(
                $definition,
                [
                    'float' => 'double',
                    'double' => 'double',
                    'number' => 'decimal',
                    'integer' => 'integer',
                    'blob' => 'binary',
                    'clob' => 'text',
                    'timestamp' => 'timestamp',
                    'string' => 'string',
                ]
            )
        );
    }
}
