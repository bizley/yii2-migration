<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\TableMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
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
        $this->db->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $this->mapper = new TableMapper($this->db);
    }

    /**
     * @param array<ForeignKeyConstraint> $foreignKeys
     * @param array<IndexConstraint> $indexes
     * @param Constraint|null $primaryKey
     */
    private function prepareSchemaMock(
        array $foreignKeys = [],
        array $indexes = [],
        Constraint $primaryKey = null
    ): void {
        $this->schema->method('getTableForeignKeys')->willReturn($foreignKeys);
        $this->schema->method('getTableIndexes')->willReturn($indexes);
        $this->schema->method('getTablePrimaryKey')->willReturn($primaryKey);
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldSetProperDefaultValues(): void
    {
        $this->prepareSchemaMock();

        $this->mapper->mapTable('abcdef');
        $structure = $this->mapper->getStructure();
        $this->assertSame('abcdef', $structure->getName());
        $this->assertNull($structure->getPrimaryKey());
        $this->assertSame([], $structure->getForeignKeys());
        $this->assertSame([], $structure->getIndexes());
        $this->assertSame([], $structure->getColumns());
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
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldSetProperPrimaryKey(string $primaryKeyName, array $primaryKeyColumns): void
    {
        $primaryKey = new Constraint([
            'name' => $primaryKeyName,
            'columnNames' => $primaryKeyColumns
        ]);

        $this->prepareSchemaMock([], [], $primaryKey);

        $this->mapper->mapTable('abcdef');
        $structurePrimaryKey = $this->mapper->getStructure()->getPrimaryKey();
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
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldSetProperForeignKeys(array $foreignKeyData): void
    {
        $foreignKeys = [];
        foreach ($foreignKeyData as $foreignKeyDatum) {
            $foreignKeys[] = new ForeignKeyConstraint($foreignKeyDatum);
        }

        $this->prepareSchemaMock($foreignKeys);

        $this->mapper->mapTable('abcdef');

        foreach ($foreignKeys as $foreignKey) {
            $structureForeignKey = $this->mapper->getStructure()->getForeignKey($foreignKey->name);

            $this->assertSame($foreignKey->columnNames, $structureForeignKey->getColumns());
            $this->assertSame($foreignKey->foreignTableName, $structureForeignKey->getReferencedTable());
            $this->assertSame($foreignKey->foreignColumnNames, $structureForeignKey->getReferencedColumns());
            $this->assertSame($foreignKey->onDelete, $structureForeignKey->getOnDelete());
            $this->assertSame($foreignKey->onUpdate, $structureForeignKey->getOnUpdate());
        }
    }
}
