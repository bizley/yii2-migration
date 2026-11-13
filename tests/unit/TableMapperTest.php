<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Schema;
use bizley\migration\table\CharacterColumn;
use bizley\migration\TableMapper;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\Command;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\Expression;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\mssql\Schema as MssqlSchema;
use yii\db\mssql\TableSchema as MssqlTableSchema;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\pgsql\Schema as PgsqlSchema;
use yii\db\QueryBuilder;
use yii\db\TableSchema;

/** @group tablemapper */
final class TableMapperTest extends TestCase
{
    /** @var MockObject&Connection */
    private $db;

    /** @var TableMapper */
    private $mapper;

    /** @var MockObject&MysqlSchema */
    private $schema;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->schema = $this->createMock(MysqlSchema::class);
        $this->db->method('getSchema')->willReturn($this->schema);
        $this->mapper = new TableMapper($this->db);
    }

    /**
     * @param bool $mockTableSchema
     * @param array<ForeignKeyConstraint> $foreignKeys
     * @param array<IndexConstraint> $indexes
     * @param Constraint|null $primaryKey
     */
    private function prepareSchemaMock(
        bool $mockTableSchema = true,
        array $foreignKeys = [],
        array $indexes = [],
        ?Constraint $primaryKey = null
    ): void {
        $this->schema->method('getTableForeignKeys')->willReturn($foreignKeys);
        $this->schema->method('getTableIndexes')->willReturn($indexes);
        $this->schema->method('getTablePrimaryKey')->willReturn($primaryKey);
        $this->schema->method('getQueryBuilder')->willReturn($this->createMock(QueryBuilder::class));

        if ($mockTableSchema) {
            $this->db->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        }
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSetProperDefaultValues(): void
    {
        $this->prepareSchemaMock();

        $structure = $this->mapper->getStructureOf('abcdef');
        self::assertSame('abcdef', $structure->getName());
        self::assertNull($structure->getPrimaryKey());
        self::assertSame([], $structure->getForeignKeys());
        self::assertSame([], $structure->getIndexes());
        self::assertSame([], $structure->getColumns());
        self::assertSame([], $this->mapper->getSuppressedForeignKeys());
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public function providerForPrimaryKey(): array
    {
        return [
            'pk1' => ['pk1', []],
            'pk2' => ['pk2', ['aaa']],
            'pk3' => ['pk3', ['aaa', 'bbb']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForPrimaryKey
     * @param string $primaryKeyName
     * @param list<string> $primaryKeyColumns
     * @throws NotSupportedException
     */
    public function shouldSetProperPrimaryKey(string $primaryKeyName, array $primaryKeyColumns): void
    {
        $primaryKey = new Constraint([
            'name' => $primaryKeyName,
            'columnNames' => $primaryKeyColumns
        ]);

        $this->prepareSchemaMock(true, [], [], $primaryKey);

        $structurePrimaryKey = $this->mapper->getStructureOf('abcdef')->getPrimaryKey();
        self::assertNotNull($structurePrimaryKey);
        self::assertSame($primaryKey->name, $structurePrimaryKey->getName());
        self::assertSame($primaryKey->columnNames, $structurePrimaryKey->getColumns());
    }

    /**
     * @return array<string, array{list<array<string, string|list<string>|null>>}>
     */
    public function providerForForeignKeys(): array
    {
        return [
            'fk1' => [[[
                'name' => 'fk1',
                'columnNames' => [],
                'foreignTableName' => 'tab2',
                'foreignColumnNames' => [],
                'onDelete' => null,
                'onUpdate' => null,
            ]]],
            'fk2' => [[[
                'name' => 'fk2',
                'columnNames' => ['aaa'],
                'foreignTableName' => 'tab3',
                'foreignColumnNames' => ['bbb'],
                'onDelete' => 'aaa',
                'onUpdate' => null,
            ]]],
            'fk3' => [[[
                'name' => 'fk3',
                'columnNames' => ['aaa', 'bbb'],
                'foreignTableName' => 'tab4',
                'foreignColumnNames' => ['ccc', 'ddd'],
                'onDelete' => 'rrr',
                'onUpdate' => 'ttt',
            ]]],
            'fk4+5' => [[
                [
                    'name' => 'fk4',
                    'columnNames' => ['ccc'],
                    'foreignTableName' => 'tab5',
                    'foreignColumnNames' => ['eee'],
                    'onDelete' => 'UPDATE',
                    'onUpdate' => 'UPDATE',
                ],
                [
                    'name' => 'fk5',
                    'columnNames' => ['aaa', 'bbb'],
                    'foreignTableName' => 'tab4',
                    'foreignColumnNames' => ['ccc', 'ddd'],
                    'onDelete' => null,
                    'onUpdate' => null,
                ]
            ]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForForeignKeys
     * @param list<array<string, string|list<string>|null>> $foreignKeyData
     * @throws NotSupportedException
     */
    public function shouldSetProperForeignKeys(array $foreignKeyData): void
    {
        $foreignKeys = [];
        foreach ($foreignKeyData as $foreignKeyDatum) {
            $foreignKeys[] = new ForeignKeyConstraint($foreignKeyDatum);
        }

        $this->prepareSchemaMock(true, $foreignKeys);

        foreach ($foreignKeys as $foreignKey) {
            self::assertNotNull($foreignKey->name);
            $structureForeignKey = $this->mapper->getStructureOf('abcdef')->getForeignKey($foreignKey->name);
            self::assertNotNull($structureForeignKey);

            self::assertSame('abcdef', $structureForeignKey->getTableName());
            self::assertSame($foreignKey->name, $structureForeignKey->getName());
            self::assertSame($foreignKey->columnNames, $structureForeignKey->getColumns());
            self::assertSame($foreignKey->foreignTableName, $structureForeignKey->getReferredTable());
            self::assertSame($foreignKey->foreignColumnNames, $structureForeignKey->getReferredColumns());
            self::assertSame($foreignKey->onDelete, $structureForeignKey->getOnDelete());
            self::assertSame($foreignKey->onUpdate, $structureForeignKey->getOnUpdate());
        }
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSuppressForeignKey(): void
    {
        $foreignKey = new ForeignKeyConstraint(
            [
                'name' => 'suppressed',
                'columnNames' => ['abc'],
                'foreignTableName' => 'tab',
                'foreignColumnNames' => ['eee'],
            ]
        );
        $this->prepareSchemaMock(true, [$foreignKey]);

        self::assertNull($this->mapper->getStructureOf('abcdef', ['tab'])->getForeignKey('suppressed'));
        $suppressedKeys = $this->mapper->getSuppressedForeignKeys();
        self::assertNotEmpty($suppressedKeys);
        self::assertSame($suppressedKeys[0]->getName(), $foreignKey->name);
    }

    /**
     * @return array<string, array{list<array<string, string|list<string>|bool>>}>
     */
    public function providerForIndexes(): array
    {
        return [
            'i1' => [[[
                'name' => 'i1',
                'columnNames' => [],
                'isUnique' => false,
                'isPrimary' => false,
            ]]],
            'i2' => [[[
                'name' => 'i2',
                'columnNames' => ['aaa'],
                'isUnique' => true,
                'isPrimary' => false,
            ]]],
            'i3' => [[[
                'name' => 'i3',
                'columnNames' => ['aaa', 'bbb'],
                'isUnique' => false,
                'isPrimary' => false,
            ]]],
            'i4+5' => [[
                [
                    'name' => 'i4',
                    'columnNames' => ['ccc'],
                    'isUnique' => true,
                    'isPrimary' => false,
                ],
                [
                    'name' => 'i5',
                    'columnNames' => ['aaa', 'bbb'],
                    'isUnique' => false,
                    'isPrimary' => false,
                ]
            ]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForIndexes
     * @param list<array<string, string|list<string>|bool>> $indexData
     * @throws NotSupportedException
     */
    public function shouldSetProperIndexes(array $indexData): void
    {
        $indexes = [];
        foreach ($indexData as $indexDatum) {
            $indexes[] = new IndexConstraint($indexDatum);
        }

        $this->prepareSchemaMock(true, [], $indexes);

        foreach ($indexes as $index) {
            self::assertNotNull($index->name);
            $structureIndex = $this->mapper->getStructureOf('abcdef')->getIndex($index->name);
            self::assertNotNull($structureIndex);

            self::assertSame($index->name, $structureIndex->getName());
            self::assertSame($index->columnNames, $structureIndex->getColumns());
            self::assertSame($index->isUnique, $structureIndex->isUnique());
        }
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldIgnorePrimaryIndex(): void
    {
        $this->prepareSchemaMock(
            true,
            [],
            [new IndexConstraint(['name' => 'aaa', 'isPrimary' => true])]
        );

        self::assertNull($this->mapper->getStructureOf('abcdef')->getIndex('aaa'));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSetColumn(): void
    {
        $this->prepareSchemaMock(false);
        $tableSchema = $this->createMock(TableSchema::class);
        $column = new ColumnSchema(
            [
                'type' => 'char',
                'name' => 'column-name',
                'size' => 1,
                'precision' => null,
                'scale' => null,
                'allowNull' => true,
                'defaultValue' => 'a',
                'isPrimaryKey' => false,
                'unsigned' => false,
                'comment' => 'comment'
            ]
        );
        $tableSchema->columns = [$column];
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $structureColumn = $this->mapper->getStructureOf('abcdef')->getColumn('column-name');
        self::assertNotNull($structureColumn);
        self::assertInstanceOf(CharacterColumn::class, $structureColumn);
        self::assertSame('column-name', $structureColumn->getName());
        self::assertSame(1, (int)$structureColumn->getSize());
        self::assertNull($structureColumn->getPrecision());
        self::assertNull($structureColumn->getScale());
        self::assertFalse($structureColumn->isNotNull());
        self::assertSame('a', $structureColumn->getDefault());
        self::assertFalse($structureColumn->isPrimaryKey());
        self::assertFalse($structureColumn->isUnsigned());
        self::assertSame('comment', $structureColumn->getComment());
        self::assertFalse($structureColumn->isUnique());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSetNoColumns(): void
    {
        $this->prepareSchemaMock(false);
        $this->db->method('getTableSchema')->willReturn(null);

        self::assertSame([], $this->mapper->getStructureOf('abcdef')->getColumns());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSetUniqueColumnWithUniqueIndex(): void
    {
        $this->prepareSchemaMock(
            false,
            [],
            [
                new IndexConstraint(
                    [
                        'name' => 'aaa',
                        'columnNames' => ['column-name'],
                        'isPrimary' => false,
                        'isUnique' => true,
                    ]
                )
            ]
        );
        $tableSchema = $this->createMock(TableSchema::class);
        $column = new ColumnSchema(
            [
                'type' => 'char',
                'name' => 'column-name',
                'size' => 1,
                'isPrimaryKey' => false,
                'unsigned' => false,
            ]
        );
        $tableSchema->columns = [$column];
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $structureColumn = $this->mapper->getStructureOf('abcdef')->getColumn('column-name');
        self::assertNotNull($structureColumn);
        self::assertTrue($structureColumn->isUnique());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotSetUniqueColumnWithoutIndex(): void
    {
        $this->prepareSchemaMock(
            false,
            [],
            [
                new IndexConstraint(
                    [
                        'name' => 'aaa',
                        'columnNames' => ['other-column'],
                        'isPrimary' => false,
                        'isUnique' => true,
                    ]
                )
            ]
        );
        $tableSchema = $this->createMock(TableSchema::class);
        $column = new ColumnSchema(
            [
                'type' => 'char',
                'name' => 'column-name',
                'size' => 1,
                'isPrimaryKey' => false,
                'unsigned' => false,
            ]
        );
        $tableSchema->columns = [$column];
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $structureColumn = $this->mapper->getStructureOf('abcdef')->getColumn('column-name');
        self::assertNotNull($structureColumn);
        self::assertFalse($structureColumn->isUnique());
        self::assertSame('mysql', $this->mapper->getSchemaType());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnProperEngineVersion(): void
    {
        $this->prepareSchemaMock();
        $this->mapper->getStructureOf('abcdef');

        self::assertNull($this->mapper->getEngineVersion());

        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('5.7.1');
        $this->db->method('getSlavePdo')->willReturn($pdo);

        self::assertSame('5.7.1', $this->mapper->getEngineVersion());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnNullAsEngineVersionWhenThereIsException(): void
    {
        $this->prepareSchemaMock();
        $this->mapper->getStructureOf('abcdef');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willThrowException(new \Exception());
        $this->db->method('getSlavePdo')->willReturn($pdo);

        self::assertNull($this->mapper->getEngineVersion());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnNullAsEngineVersionWhenPdoIsNull(): void
    {
        $this->prepareSchemaMock();
        $this->mapper->getStructureOf('abcdef');

        $this->db->method('getSlavePdo')->willReturn(null);

        self::assertNull($this->mapper->getEngineVersion());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReadTableMetadataOnlyOnceAndBuildNewStructureEachTime(): void
    {
        $foreignKey = new ForeignKeyConstraint(
            [
                'name' => 'fk',
                'columnNames' => ['column-name'],
                'foreignTableName' => 'tab',
                'foreignColumnNames' => ['id'],
            ]
        );
        $this->schema->expects(self::once())->method('getTableForeignKeys')->willReturn([$foreignKey]);
        $this->schema->expects(self::once())->method('getTableIndexes')->willReturn([]);
        $this->schema->expects(self::once())->method('getTablePrimaryKey')->willReturn(null);
        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->columns = [new ColumnSchema(
            ['type' => 'char', 'name' => 'column-name', 'size' => 1, 'isPrimaryKey' => false, 'unsigned' => false]
        )];
        $this->db->expects(self::once())->method('getTableSchema')->willReturn($tableSchema);

        self::assertSame($tableSchema, $this->mapper->getTableSchema('abcdef'));
        $first = $this->mapper->getStructureOf('abcdef');
        self::assertSame([], $this->mapper->getSuppressedForeignKeys());
        $second = $this->mapper->getStructureOf('abcdef', ['tab']);

        self::assertNotSame($first, $second);
        self::assertNotSame($first->getColumn('column-name'), $second->getColumn('column-name'));
        self::assertNotNull($first->getForeignKey('fk'));
        self::assertNull($second->getForeignKey('fk'));
        self::assertCount(1, $this->mapper->getSuppressedForeignKeys());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReadDecimalPrecisionAndScaleForMssql(): void
    {
        $schema = $this->createMock(MssqlSchema::class);
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $schema->method('getTablePrimaryKey')->willReturn(null);
        $db = $this->createMock(Connection::class);
        $db->method('getSchema')->willReturn($schema);

        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->name = 'bill';
        $tableSchema->schemaName = 'dbo';
        $tableSchema->columns = [
            new ColumnSchema([
                'type' => 'decimal',
                'dbType' => 'decimal',
                'name' => 'Amount',
                'isPrimaryKey' => false,
                'unsigned' => false,
            ]),
            new ColumnSchema([
                'type' => 'decimal',
                'dbType' => 'numeric',
                'name' => 'Rate',
                'isPrimaryKey' => false,
                'unsigned' => false,
            ]),
        ];
        $db->method('getTableSchema')->willReturn($tableSchema);

        $command = $this->createMock(Command::class);
        $command->expects(self::once())->method('queryAll')->willReturn([
            ['column_name' => 'Amount', 'numeric_precision' => '18', 'numeric_scale' => '2'],
            ['column_name' => 'Rate', 'numeric_precision' => 10, 'numeric_scale' => 0],
        ]);
        $db->expects(self::once())
            ->method('createCommand')
            ->with(self::stringContains('INFORMATION_SCHEMA'), [':schema' => 'dbo', ':table' => 'bill'])
            ->willReturn($command);

        $mapper = new TableMapper($db);
        $structure = $mapper->getStructureOf('bill');
        $mapper->getStructureOf('bill');

        $amount = $structure->getColumn('Amount');
        self::assertNotNull($amount);
        self::assertSame(18, $amount->getPrecision());
        self::assertSame(18, $amount->getSize());
        self::assertSame(2, $amount->getScale());
        self::assertSame('18, 2', $amount->getLength(Schema::MSSQL));

        $rate = $structure->getColumn('Rate');
        self::assertNotNull($rate);
        self::assertSame(10, $rate->getPrecision());
        self::assertSame(0, $rate->getScale());
        self::assertSame('10, 0', $rate->getLength(Schema::MSSQL));
    }

    /**
     * @return MockObject&Connection
     */
    private function prepareMssqlConnection(TableSchema $tableSchema): Connection
    {
        $schema = $this->createMock(MssqlSchema::class);
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $schema->method('getTablePrimaryKey')->willReturn(null);
        $db = $this->createMock(Connection::class);
        $db->method('getSchema')->willReturn($schema);
        $db->method('getTableSchema')->willReturn($tableSchema);

        return $db;
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldQueryMssqlInformationSchemaOfTheTableCatalog(): void
    {
        $tableSchema = $this->createMock(MssqlTableSchema::class);
        $tableSchema->name = 'bill';
        $tableSchema->schemaName = 'dbo';
        $tableSchema->catalogName = 'shop';
        $tableSchema->columns = [
            new ColumnSchema(
                ['type' => 'decimal', 'dbType' => 'DECIMAL', 'name' => 'Amount', 'isPrimaryKey' => false, 'unsigned' => false]
            ),
        ];
        $db = $this->prepareMssqlConnection($tableSchema);

        $command = $this->createMock(Command::class);
        $command->method('queryAll')->willReturn(
            [['column_name' => 'Amount', 'numeric_precision' => 12, 'numeric_scale' => 4]]
        );
        $db->expects(self::once())
            ->method('createCommand')
            ->with(self::stringContains('FROM [shop].[INFORMATION_SCHEMA].[COLUMNS]'))
            ->willReturn($command);

        $amount = (new TableMapper($db))->getStructureOf('bill')->getColumn('Amount');
        self::assertNotNull($amount);
        self::assertSame('12, 4', $amount->getLength(Schema::MSSQL));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotQueryMssqlInformationSchemaWhenNotNeeded(): void
    {
        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->name = 'bill';
        $tableSchema->columns = [
            new ColumnSchema([
                'type' => 'decimal',
                'dbType' => 'decimal',
                'name' => 'Amount',
                'precision' => 9,
                'scale' => 3,
                'isPrimaryKey' => false,
                'unsigned' => false,
            ]),
            new ColumnSchema(
                [
                    'type' => 'string',
                    'dbType' => 'varchar(20)',
                    'name' => 'Code',
                    'size' => 20,
                    'isPrimaryKey' => false,
                    'unsigned' => false,
                ]
            ),
        ];
        $db = $this->prepareMssqlConnection($tableSchema);
        $db->expects(self::never())->method('createCommand');

        $amount = (new TableMapper($db))->getStructureOf('bill')->getColumn('Amount');
        self::assertNotNull($amount);
        self::assertSame('9, 3', $amount->getLength(Schema::MSSQL));
    }

    /**
     * @param array<ColumnSchema> $columns
     * @return MockObject&Connection
     */
    private function preparePgsqlConnection(array $columns): Connection
    {
        $schema = $this->createMock(PgsqlSchema::class);
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $schema->method('getTablePrimaryKey')->willReturn(null);
        $db = $this->createMock(Connection::class);
        $db->method('getSchema')->willReturn($schema);

        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->name = 'tab';
        $tableSchema->schemaName = 'public';
        $tableSchema->columns = $columns;
        $db->method('getTableSchema')->willReturn($tableSchema);

        return $db;
    }

    /**
     * @param mixed $default
     */
    private function pgsqlColumn(string $name, $default, bool $isPrimaryKey = false): ColumnSchema
    {
        return new ColumnSchema([
            'type' => 'string',
            'dbType' => 'varchar',
            'name' => $name,
            'defaultValue' => $default,
            'isPrimaryKey' => $isPrimaryKey,
            'unsigned' => false,
        ]);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function providerForPgsqlRawDefaults(): array
    {
        return [
            'quoted string' => ["'abc'::character varying", false],
            'quoted string with escaped quote' => ["'it''s'::character varying", false],
            'empty string' => ["''::character varying", false],
            'quoted negative number' => ["'-5'::integer", false],
            'quoted array' => ["'{1,2}'::integer[]", false],
            'quoted json' => ["'{\"a\":\"b\"}'::json", false],
            'quoted timestamp' => ["'2020-01-01 10:00:00'::timestamp without time zone", false],
            'quoted with sized cast' => ["'abc'::character varying(10)", false],
            'quoted bit' => ["'101'::\"bit\"", false],
            'bit literal' => ["B'101'::\"bit\"", false],
            'integer' => ['5', false],
            'decimal' => ['1.5', false],
            'negative decimal' => ['-1.5', false],
            'true' => ['true', false],
            'false' => ['false', false],
            'null with cast' => ['NULL::character varying', false],
            'function' => ['gen_random_uuid()', true],
            'function with cast argument' => ['md5((random())::text)', true],
            'casted function' => ["(md5('x'::text))::uuid", true],
            'now' => ['now()', true],
            'current timestamp' => ['CURRENT_TIMESTAMP', true],
            'arithmetic' => ['(1 + 2)', true],
            'concatenation' => ["('a'::text || 'b'::text)", true],
            'quoted and operator' => ["'1'::integer + 1", true],
            'sequence' => ["nextval('tab_col_seq'::regclass)", true],
        ];
    }

    /**
     * @test
     * @dataProvider providerForPgsqlRawDefaults
     * @throws NotSupportedException
     */
    public function shouldUseRawPgsqlDefaultForExpressions(string $rawDefault, bool $isExpression): void
    {
        $db = $this->preparePgsqlConnection([$this->pgsqlColumn('col', 'parsed by Yii')]);
        $command = $this->createMock(Command::class);
        $command->method('queryAll')->willReturn([['column_name' => 'col', 'column_default' => $rawDefault]]);
        $db->expects(self::once())
            ->method('createCommand')
            ->with(self::stringContains('pg_attrdef'), [':schema' => 'public', ':table' => 'tab'])
            ->willReturn($command);

        $column = (new TableMapper($db))->getStructureOf('tab')->getColumn('col');
        self::assertNotNull($column);
        if ($isExpression) {
            $default = $column->getDefault();
            self::assertInstanceOf(Expression::class, $default);
            self::assertSame($rawDefault, $default->expression);
        } else {
            self::assertSame('parsed by Yii', $column->getDefault());
        }
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReadPgsqlDefaultsOnlyOncePerTable(): void
    {
        $db = $this->preparePgsqlConnection([
            $this->pgsqlColumn('id', null, true),
            $this->pgsqlColumn('a', 'gen_random_uuid()'),
            $this->pgsqlColumn('b', 'abc'),
            $this->pgsqlColumn('c', null),
        ]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())->method('queryAll')->willReturn([
            ['column_name' => 'id', 'column_default' => "nextval('tab_id_seq'::regclass)"],
            ['column_name' => 'a', 'column_default' => 'gen_random_uuid()'],
            ['column_name' => 'b', 'column_default' => "'abc'::character varying"],
        ]);
        $db->expects(self::once())->method('createCommand')->willReturn($command);

        $mapper = new TableMapper($db);
        $structure = $mapper->getStructureOf('tab');
        $mapper->getStructureOf('tab');

        self::assertNull($structure->getColumn('id')?->getDefault());
        self::assertEquals(new Expression('gen_random_uuid()'), $structure->getColumn('a')?->getDefault());
        self::assertSame('abc', $structure->getColumn('b')?->getDefault());
        self::assertNull($structure->getColumn('c')?->getDefault());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotReadPgsqlDefaultsWhenThereAreNone(): void
    {
        $db = $this->preparePgsqlConnection([
            $this->pgsqlColumn('id', null, true),
            $this->pgsqlColumn('a', null),
        ]);
        $db->expects(self::never())->method('createCommand');

        (new TableMapper($db))->getStructureOf('tab');
    }

    private function plainIndex(string $name, string $column): IndexConstraint
    {
        return new IndexConstraint(
            ['name' => $name, 'columnNames' => [$column], 'isPrimary' => false, 'isUnique' => false]
        );
    }

    /**
     * @param array<IndexConstraint> $indexes
     * @param array<ColumnSchema> $columns
     * @return MockObject&Connection
     */
    private function preparePgsqlIndexConnection(array $indexes, array $columns = []): Connection
    {
        $schema = $this->createMock(PgsqlSchema::class);
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn($indexes);
        $schema->method('getTablePrimaryKey')->willReturn(null);
        $db = $this->createMock(Connection::class);
        $db->method('getSchema')->willReturn($schema);

        $tableSchema = $this->createMock(TableSchema::class);
        $tableSchema->name = 'tab';
        $tableSchema->schemaName = 'public';
        $tableSchema->columns = $columns;
        $db->method('getTableSchema')->willReturn($tableSchema);

        return $db;
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReadPgsqlIndexMethodsAndDefinitions(): void
    {
        $db = $this->preparePgsqlIndexConnection([
            new IndexConstraint(['name' => 'pk', 'columnNames' => ['id'], 'isPrimary' => true, 'isUnique' => true]),
            $this->plainIndex('idx-hash', 'a'),
            $this->plainIndex('idx-btree', 'b'),
            $this->plainIndex('idx-gin', 'c'),
            $this->plainIndex('idx-opclass', 'd'),
            $this->plainIndex('idx-expr', 'lower'),
            new IndexConstraint(
                ['name' => 'idx-partial', 'columnNames' => ['e'], 'isPrimary' => false, 'isUnique' => true]
            ),
            $this->plainIndex('idx-multi', 'f'),
            $this->plainIndex('idx-unknown', 'g'),
        ]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())->method('queryAll')->willReturn([
            [
                'name' => 'idx-hash',
                'method' => 'hash',
                'definition' => 'CREATE INDEX "idx-hash" ON public.tab USING hash (a)',
                'plain_columns' => 'a',
            ],
            [
                'name' => 'idx-btree',
                'method' => 'btree',
                'definition' => 'CREATE INDEX "idx-btree" ON public.tab USING btree (b)',
                'plain_columns' => 'b',
            ],
            [
                'name' => 'idx-gin',
                'method' => 'gin',
                'definition' => 'CREATE INDEX "idx-gin" ON public.tab USING gin (c)',
                'plain_columns' => 'c',
            ],
            [
                'name' => 'idx-opclass',
                'method' => 'gin',
                'definition' => 'CREATE INDEX "idx-opclass" ON public.tab USING gin (d gin_trgm_ops)',
                'plain_columns' => 'd',
            ],
            [
                'name' => 'idx-expr',
                'method' => 'btree',
                'definition' => 'CREATE INDEX "idx-expr" ON public.tab USING btree (lower((h)::text))',
                'plain_columns' => null,
            ],
            [
                'name' => 'idx-partial',
                'method' => 'btree',
                'definition' => 'CREATE UNIQUE INDEX "idx-partial" ON public.tab USING btree (e) WHERE (e > 0)',
                'plain_columns' => 'e',
            ],
            [
                'name' => 'idx-multi',
                'method' => 'btree',
                'definition' => 'CREATE INDEX "idx-multi" ON public.tab USING btree (f, "G")',
                'plain_columns' => 'f, "G"',
            ],
            [
                'name' => 'idx-unknown',
                'method' => 'btree',
                'definition' => 'something unexpected',
                'plain_columns' => 'g',
            ],
        ]);
        $db->expects(self::once())
            ->method('createCommand')
            ->with(self::stringContains('pg_get_indexdef'), [':schema' => 'public', ':table' => 'tab'])
            ->willReturn($command);

        $mapper = new TableMapper($db);
        $structure = $mapper->getStructureOf('tab');
        $mapper->getStructureOf('tab');

        $expected = [
            'idx-hash' => ['hash', null, ['a']],
            'idx-btree' => [null, null, ['b']],
            'idx-gin' => ['gin', null, ['c']],
            'idx-opclass' => [null, 'USING gin (d gin_trgm_ops)', []],
            'idx-expr' => [null, 'USING btree (lower((h)::text))', []],
            'idx-partial' => [null, 'USING btree (e) WHERE (e > 0)', []],
            'idx-multi' => [null, null, ['f']],
            'idx-unknown' => [null, null, ['g']],
        ];
        foreach ($expected as $name => [$method, $definition, $columns]) {
            $index = $structure->getIndex($name);
            self::assertNotNull($index, $name);
            self::assertSame($method, $index->getMethod(), $name);
            self::assertSame($definition, $index->getDefinition(), $name);
            self::assertSame($columns, $index->getColumns(), $name);
        }
        self::assertTrue($structure->getIndex('idx-partial')?->isUnique());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotMarkColumnAsUniqueBecauseOfUniqueIndexWithDefinition(): void
    {
        $db = $this->preparePgsqlIndexConnection(
            [
                new IndexConstraint(
                    ['name' => 'idx-partial', 'columnNames' => ['col'], 'isPrimary' => false, 'isUnique' => true]
                ),
            ],
            [new ColumnSchema(['type' => 'integer', 'name' => 'col', 'isPrimaryKey' => false, 'unsigned' => false])]
        );
        $command = $this->createMock(Command::class);
        $command->method('queryAll')->willReturn([
            [
                'name' => 'idx-partial',
                'method' => 'btree',
                'definition' => 'CREATE UNIQUE INDEX "idx-partial" ON public.tab USING btree (col) WHERE (col > 0)',
                'plain_columns' => 'col',
            ],
        ]);
        $db->method('createCommand')->willReturn($command);

        self::assertFalse((new TableMapper($db))->getStructureOf('tab')->getColumn('col')?->isUnique());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotReadPgsqlIndexMethodsWhenThereAreNoIndexes(): void
    {
        $db = $this->preparePgsqlIndexConnection([
            new IndexConstraint(['name' => 'pk', 'columnNames' => ['id'], 'isPrimary' => true, 'isUnique' => true]),
        ]);
        $db->expects(self::never())->method('createCommand');

        (new TableMapper($db))->getStructureOf('tab');
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotReadIndexMethodsForNonPgsql(): void
    {
        $this->prepareSchemaMock(
            true,
            [],
            [$this->plainIndex('idx', 'a')]
        );
        $this->db->expects(self::never())->method('createCommand');

        self::assertNull($this->mapper->getStructureOf('tab')->getIndex('idx')?->getMethod());
    }
}
