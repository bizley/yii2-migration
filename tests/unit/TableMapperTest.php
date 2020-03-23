<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\table\CharacterColumn;
use bizley\migration\TableMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\mysql\Schema;
use yii\db\TableSchema;

class TableMapperTest extends TestCase
{
    /** @var MockObject|Connection */
    private $db;

    /** @var TableMapper */
    private $mapper;

    /** @var MockObject|Schema */
    private $schema;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->schema = $this->createMock(Schema::class);
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
        Constraint $primaryKey = null
    ): void {
        $this->schema->method('getTableForeignKeys')->willReturn($foreignKeys);
        $this->schema->method('getTableIndexes')->willReturn($indexes);
        $this->schema->method('getTablePrimaryKey')->willReturn($primaryKey);

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
        $this->assertSame('abcdef', $structure->getName());
        $this->assertNull($structure->getPrimaryKey());
        $this->assertSame([], $structure->getForeignKeys());
        $this->assertSame([], $structure->getIndexes());
        $this->assertSame([], $structure->getColumns());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldSetProperDefaultValuesInBatch(): void
    {
        $this->prepareSchemaMock();

        $batch = $this->mapper->getStructuresOf(
            [
                'abc' => [],
                'def' => [],
            ]
        );

        $structure1 = $batch->get('abc');
        $structure2 = $batch->get('def');
        $this->assertNull($batch->get('other'));

        $this->assertSame('abc', $structure1->getName());
        $this->assertNull($structure1->getPrimaryKey());
        $this->assertSame([], $structure1->getForeignKeys());
        $this->assertSame([], $structure1->getIndexes());
        $this->assertSame([], $structure1->getColumns());

        $this->assertSame('def', $structure2->getName());
        $this->assertNull($structure2->getPrimaryKey());
        $this->assertSame([], $structure2->getForeignKeys());
        $this->assertSame([], $structure2->getIndexes());
        $this->assertSame([], $structure2->getColumns());
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
        $this->assertSame($primaryKey->name, $structurePrimaryKey->getName());
        $this->assertSame($primaryKey->columnNames, $structurePrimaryKey->getColumns());
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

            $this->assertSame($foreignKey->name, $structureForeignKey->getName());
            $this->assertSame($foreignKey->columnNames, $structureForeignKey->getColumns());
            $this->assertSame($foreignKey->foreignTableName, $structureForeignKey->getReferencedTable());
            $this->assertSame($foreignKey->foreignColumnNames, $structureForeignKey->getReferencedColumns());
            $this->assertSame($foreignKey->onDelete, $structureForeignKey->getOnDelete());
            $this->assertSame($foreignKey->onUpdate, $structureForeignKey->getOnUpdate());
        }
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

            $this->assertSame($index->name, $structureIndex->getName());
            $this->assertSame($index->columnNames, $structureIndex->getColumns());
            $this->assertSame($index->isUnique, $structureIndex->isUnique());
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

        $this->assertNull($this->mapper->getStructureOf('abcdef')->getIndex('aaa'));
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
        $this->assertNotNull($structureColumn);
        $this->assertInstanceOf(CharacterColumn::class, $structureColumn);
        $this->assertSame('column-name', $structureColumn->getName());
        $this->assertSame(1, $structureColumn->getSize());
        $this->assertNull($structureColumn->getPrecision());
        $this->assertNull($structureColumn->getScale());
        $this->assertNull($structureColumn->isNotNull());
        $this->assertSame('a', $structureColumn->getDefault());
        $this->assertFalse($structureColumn->isPrimaryKey());
        $this->assertFalse($structureColumn->isUnsigned());
        $this->assertSame('comment', $structureColumn->getComment());
        $this->assertFalse($structureColumn->isUnique());
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
        $this->assertNotNull($structureColumn);
        $this->assertTrue($structureColumn->isUnique());
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
        $this->assertNotNull($structureColumn);
        $this->assertFalse($structureColumn->isUnique());
    }
}
