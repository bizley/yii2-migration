<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\SqlColumnMapper;
use PHPUnit\Framework\TestCase;
use yii\db\cubrid\Schema as CubridSchema;
use yii\db\mssql\Schema as MSSqlSchema;
use yii\db\mysql\Schema as MySqlSchema;
use yii\db\pgsql\Schema as PostgreSqlSchema;
use yii\db\sqlite\Schema as SqliteSchema;

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
        self::assertSame($schema, SqlColumnMapper::map($definition, (new CubridSchema())->typeMap));
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
        self::assertSame($schema, SqlColumnMapper::map($definition, (new MSSqlSchema())->typeMap));
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
        self::assertSame($schema, SqlColumnMapper::map($definition, (new MySqlSchema())->typeMap));
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
        self::assertSame(
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

    public function providerForPostgreSql(): array
    {
        return [
            ['bit', ['type' => 'integer']],
            ['bit varying', ['type' => 'integer']],
            ['varbit', ['type' => 'integer']],
            ['bool', ['type' => 'boolean']],
            ['boolean', ['type' => 'boolean']],
            ['box', ['type' => 'string']],
            ['circle', ['type' => 'string']],
            ['point', ['type' => 'string']],
            ['line', ['type' => 'string']],
            ['lseg', ['type' => 'string']],
            ['polygon', ['type' => 'string']],
            ['path', ['type' => 'string']],
            ['character', ['type' => 'char']],
            ['char', ['type' => 'char']],
            ['bpchar', ['type' => 'char']],
            ['character varying', ['type' => 'string']],
            ['varchar', ['type' => 'string']],
            ['text', ['type' => 'text']],
            ['bytea', ['type' => 'binary']],
            ['cidr', ['type' => 'string']],
            ['inet', ['type' => 'string']],
            ['macaddr', ['type' => 'string']],
            ['real', ['type' => 'float']],
            ['float4', ['type' => 'float']],
            ['double precision', ['type' => 'double']],
            ['float8', ['type' => 'double']],
            ['decimal', ['type' => 'decimal']],
            ['numeric', ['type' => 'decimal']],
            ['money', ['type' => 'money']],
            ['smallint', ['type' => 'smallint']],
            ['int2', ['type' => 'smallint']],
            ['int4', ['type' => 'integer']],
            ['int', ['type' => 'integer']],
            ['integer', ['type' => 'integer']],
            ['bigint', ['type' => 'bigint']],
            ['int8', ['type' => 'bigint']],
            ['oid', ['type' => 'bigint']],
            ['smallserial', ['type' => 'smallint']],
            ['serial2', ['type' => 'smallint']],
            ['serial4', ['type' => 'integer']],
            ['serial', ['type' => 'integer']],
            ['bigserial', ['type' => 'bigint']],
            ['serial8', ['type' => 'bigint']],
            ['pg_lsn', ['type' => 'bigint']],
            ['date', ['type' => 'date']],
            ['interval', ['type' => 'string']],
            ['time without time zone', ['type' => 'time']],
            ['time', ['type' => 'time']],
            ['time with time zone', ['type' => 'time']],
            ['timetz', ['type' => 'time']],
            ['timestamp without time zone', ['type' => 'timestamp']],
            ['timestamp', ['type' => 'timestamp']],
            ['timestamp with time zone', ['type' => 'timestamp']],
            ['timestamptz', ['type' => 'timestamp']],
            ['abstime', ['type' => 'timestamp']],
            ['tsquery', ['type' => 'string']],
            ['tsvector', ['type' => 'string']],
            ['txid_snapshot', ['type' => 'string']],
            ['unknown', ['type' => 'string']],
            ['uuid', ['type' => 'string']],
            ['json', ['type' => 'json']],
            ['jsonb', ['type' => 'json']],
            ['xml', ['type' => 'string']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForPostgreSql
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectPostgreSqlTypes(string $definition, array $schema): void
    {
        self::assertSame($schema, SqlColumnMapper::map($definition, (new PostgreSqlSchema())->typeMap));
    }

    public function providerForSqlite(): array
    {
        return [
            ['tinyint', ['type' => 'tinyint']],
            ['bit', ['type' => 'smallint']],
            ['boolean', ['type' => 'boolean']],
            ['bool', ['type' => 'boolean']],
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
            ['text', ['type' => 'text']],
            ['varchar', ['type' => 'string']],
            ['string', ['type' => 'string']],
            ['char', ['type' => 'char']],
            ['blob', ['type' => 'binary']],
            ['datetime', ['type' => 'datetime']],
            ['year', ['type' => 'date']],
            ['date', ['type' => 'date']],
            ['time', ['type' => 'time']],
            ['timestamp', ['type' => 'timestamp']],
            ['enum', ['type' => 'string']],
            ['xxx', ['type' => 'string', 'append' => 'xxx']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSqlite
     * @param string $definition
     * @param array $schema
     */
    public function shouldDetectSqliteTypes(string $definition, array $schema): void
    {
        self::assertSame($schema, SqlColumnMapper::map($definition, (new SqliteSchema())->typeMap));
    }

    /** @test */
    public function shouldDetectTypeWithoutLength(): void
    {
        self::assertSame(['type' => 'string'], SqlColumnMapper::map('varchar', ['varchar' => 'string']));
    }

    /** @test */
    public function shouldDetectTypeWithLength(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'length' => '255'
            ],
            SqlColumnMapper::map('varchar(255)', ['varchar' => 'string'])
        );
    }

    /** @test */
    public function shouldDetectEnum(): void
    {
        self::assertSame(
            [
                'type' => 'string',
            ],
            SqlColumnMapper::map("enum('one', 'two')", ['enum' => 'string'])
        );
    }

    /** @test */
    public function shouldDetectTypeWithLengthVariant2(): void
    {
        self::assertSame(
            [
                'type' => 'float',
                'length' => '5,2'
            ],
            SqlColumnMapper::map('float(5, 2)', ['float' => 'float'])
        );
    }

    /** @test */
    public function shouldDetectComment(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'comment' => 'test',
            ],
            SqlColumnMapper::map('comment \'test\'', [])
        );
    }

    /** @test */
    public function shouldDetectCommentWithQuote(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'comment' => "te''st",
            ],
            SqlColumnMapper::map("comment 'te''st'", [])
        );
    }

    /** @test */
    public function shouldDetectStringDefault(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'default' => "test",
            ],
            SqlColumnMapper::map("default 'test'", [])
        );
    }

    /** @test */
    public function shouldDetectNumericDefault(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'default' => '12',
            ],
            SqlColumnMapper::map('default 12', [])
        );
    }

    /** @test */
    public function shouldDetectNumericDefaultWithDot(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'default' => '1.5',
            ],
            SqlColumnMapper::map('default 1.5', [])
        );
    }

    /** @test */
    public function shouldDetectParenthesizedDefault(): void
    {
        $schema = SqlColumnMapper::map('default (default (value))', []);
        self::assertSame('string', $schema['type']);
        self::assertSame('(default (value))', $schema['default']->expression);
    }

    /** @test */
    public function shouldDetectExpressionDefault(): void
    {
        $schema = SqlColumnMapper::map('default CURRENT_TIMESTAMP', []);
        self::assertSame('string', $schema['type']);
        self::assertSame('CURRENT_TIMESTAMP', $schema['default']->expression);
    }

    /** @test */
    public function shouldDetectParenthesizedExpressionDefault(): void
    {
        $schema = SqlColumnMapper::map('default NOW()', []);
        self::assertSame('string', $schema['type']);
        self::assertSame('NOW()', $schema['default']->expression);
    }

    /** @test */
    public function shouldDetectFirst(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isFirst' => true,
            ],
            SqlColumnMapper::map('first', [])
        );
    }

    /** @test */
    public function shouldDetectAfter(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'after' => 'col',
            ],
            SqlColumnMapper::map('after `col`', [])
        );
    }

    /** @test */
    public function shouldDetectNotNull(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isNotNull' => true,
            ],
            SqlColumnMapper::map('not null', [])
        );
    }

    /** @test */
    public function shouldDetectNull(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isNotNull' => false,
            ],
            SqlColumnMapper::map('null', [])
        );
    }

    /** @test */
    public function shouldDetectAutoincrement(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'autoIncrement' => true,
            ],
            SqlColumnMapper::map('AUTOINCREMENT', [])
        );
    }

    /** @test */
    public function shouldDetectAutoincrementVariant2(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'autoIncrement' => true,
            ],
            SqlColumnMapper::map('AUTO_INCREMENT', [])
        );
    }

    /** @test */
    public function shouldDetectPrimaryKey(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isPrimaryKey' => true,
            ],
            SqlColumnMapper::map('IDENTITY PRIMARY KEY', [])
        );
    }

    /** @test */
    public function shouldDetectPrimaryKeyVariant2(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isPrimaryKey' => true,
            ],
            SqlColumnMapper::map('PRIMARY KEY', [])
        );
    }

    /** @test */
    public function shouldDetectUnsigned(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isUnsigned' => true,
            ],
            SqlColumnMapper::map('unsigned', [])
        );
    }

    /** @test */
    public function shouldDetectUnique(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isUnique' => true,
            ],
            SqlColumnMapper::map('Unique', [])
        );
    }

    /** @test */
    public function shouldPrepareAppend(): void
    {
        self::assertSame(
            [
                'type' => 'string',
                'isNotNull' => true,
                'isPrimaryKey' => true,
                'append' => 'test'
            ],
            SqlColumnMapper::map('primary key not null test', [])
        );
    }
}
