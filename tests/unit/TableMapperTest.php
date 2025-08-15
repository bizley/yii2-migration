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
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\QueryBuilder;
use yii\db\TableSchema;

/** @group tablemapper */
final class TableMapperTest extends TestCase
{
    /** @var MockObject&Connection */
    private $db;

    /** @var TableMapper */
    private $mapper;

    /** @var MockObject&Schema */
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
     * @param array $primaryKeyColumns
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
        self::assertSame($primaryKey->name, $structurePrimaryKey->getName());
        self::assertSame($primaryKey->columnNames, $structurePrimaryKey->getColumns());
    }

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
     * @param array $foreignKeyData
     * @throws NotSupportedException
     */
    public function shouldSetProperForeignKeys(array $foreignKeyData): void
    {
        $foreignKeys = [];
        foreach ($foreignKeyData as $foreignKeyDatum) {
            $foreignKeys[] = new ForeignKeyConstraint($foreignKeyDatum);
        }

        $this->prepareSchemaMock(true, $foreignKeys);

        /** @var ForeignKeyConstraint $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $structureForeignKey = $this->mapper->getStructureOf('abcdef')->getForeignKey($foreignKey->name);

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

        self::assertNull($this->mapper->getStructureOf('abcdef', ['tab'])->getForeignKey($foreignKey->name));
        $suppressedKeys = $this->mapper->getSuppressedForeignKeys();
        self::assertNotEmpty($suppressedKeys);
        self::assertSame($suppressedKeys[0]->getName(), $foreignKey->name);
    }

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
     * @param array $indexData
     * @throws NotSupportedException
     */
    public function shouldSetProperIndexes(array $indexData): void
    {
        $indexes = [];
        foreach ($indexData as $indexDatum) {
            $indexes[] = new IndexConstraint($indexDatum);
        }

        $this->prepareSchemaMock(true, [], $indexes);

        /** @var IndexConstraint $index */
        foreach ($indexes as $index) {
            $structureIndex = $this->mapper->getStructureOf('abcdef')->getIndex($index->name);

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
}
