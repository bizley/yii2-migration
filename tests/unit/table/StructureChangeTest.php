<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\CharacterColumn;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\IntegerColumn;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\StructureChange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use yii\db\Schema;

class StructureChangeTest extends TestCase
{
    /** @var StructureChange */
    private $change;

    protected function setUp(): void
    {
        $this->change = new StructureChange();
    }

    /** @test */
    public function shouldProperlySetTable(): void
    {
        $this->change->setTable('test');
        $this->assertSame('test', $this->change->getTable());
    }

    /** @test */
    public function shouldReturnDataForUnknownMethod(): void
    {
        $this->change->setMethod('unknown');
        $this->change->setData('data');

        $this->assertSame('data', $this->change->getValue());
    }

    public function providerForWrongCreateTable(): array
    {
        return [
            ['wrong'],
            [['column' => []]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongCreateTable
     * @param mixed $data
     */
    public function shouldThrowExceptionForWrongDataForCreateTable($data): void
    {
        $this->change->setMethod('createTable');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForCreateTableWithDefaults(): void
    {
        $this->change->setMethod('createTable');
        $this->change->setData(['column' => ['type' => Schema::TYPE_INTEGER]]);

        $columns = $this->change->getValue();
        $this->assertCount(1, $columns);
        /** @var ColumnInterface $column */
        $column = $columns[0];
        $this->assertInstanceOf(IntegerColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertNull($column->getLength());
        $this->assertNull($column->isNotNull());
        $this->assertFalse($column->isUnique());
        $this->assertFalse($column->isAutoIncrement());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertNull($column->getDefault());
        $this->assertNull($column->getAppend());
        $this->assertFalse($column->isUnsigned());
        $this->assertNull($column->getComment());
        $this->assertNull($column->getAfter());
        $this->assertFalse($column->isFirst());
    }

    /** @test */
    public function shouldProperlyReturnValueForCreateTableWithNonDefaults(): void
    {
        $this->change->setMethod('createTable');
        $this->change->setData(
            [
                'column' => [
                    'type' => Schema::TYPE_CHAR,
                    'length' => 10,
                    'isNotNull' => true,
                    'isUnique' => true,
                    'autoIncrement' => true,
                    'isPrimaryKey' => true,
                    'default' => 'default-value',
                    'append' => 'append-value',
                    'isUnsigned' => true,
                    'comment' => 'comment-value',
                    'after' => 'after-column',
                    'isFirst' => true,
                ]
            ]
        );

        $columns = $this->change->getValue();
        $this->assertCount(1, $columns);
        /** @var ColumnInterface $column */
        $column = $columns[0];
        $this->assertInstanceOf(CharacterColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertSame(10, $column->getLength());
        $this->assertTrue($column->isNotNull());
        $this->assertTrue($column->isUnique());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertSame('default-value', $column->getDefault());
        $this->assertSame('append-value', $column->getAppend());
        $this->assertTrue($column->isUnsigned());
        $this->assertSame('comment-value', $column->getComment());
        $this->assertSame('after-column', $column->getAfter());
        $this->assertTrue($column->isFirst());
    }

    public function providerForWrongAddColumn(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, []]],
            [['col', 1]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongAddColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForAddColumnWithWrongData($data): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForAddColumnWithDefaults(): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData(
            [
                'column',
                ['type' => Schema::TYPE_INTEGER]
            ]
        );

        $column = $this->change->getValue();
        $this->assertInstanceOf(IntegerColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertNull($column->getLength());
        $this->assertNull($column->isNotNull());
        $this->assertFalse($column->isUnique());
        $this->assertFalse($column->isAutoIncrement());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertNull($column->getDefault());
        $this->assertNull($column->getAppend());
        $this->assertFalse($column->isUnsigned());
        $this->assertNull($column->getComment());
        $this->assertNull($column->getAfter());
        $this->assertFalse($column->isFirst());
    }

    /** @test */
    public function shouldProperlyReturnValueForAddColumnWithNonDefaults(): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData(
            [
                'column',
                [
                    'type' => Schema::TYPE_CHAR,
                    'length' => 10,
                    'isNotNull' => true,
                    'isUnique' => true,
                    'autoIncrement' => true,
                    'isPrimaryKey' => true,
                    'default' => 'default-value',
                    'append' => 'append-value',
                    'isUnsigned' => true,
                    'comment' => 'comment-value',
                    'after' => 'after-column',
                    'isFirst' => true,
                ]
            ]
        );

        $column = $this->change->getValue();
        $this->assertInstanceOf(CharacterColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertSame(10, $column->getLength());
        $this->assertTrue($column->isNotNull());
        $this->assertTrue($column->isUnique());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertSame('default-value', $column->getDefault());
        $this->assertSame('append-value', $column->getAppend());
        $this->assertTrue($column->isUnsigned());
        $this->assertSame('comment-value', $column->getComment());
        $this->assertSame('after-column', $column->getAfter());
        $this->assertTrue($column->isFirst());
    }

    /**
     * @test
     * @dataProvider providerForWrongAddColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForAlterColumnWithWrongData($data): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForAlterColumnWithDefaults(): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData(
            [
                'column',
                ['type' => Schema::TYPE_INTEGER]
            ]
        );

        $column = $this->change->getValue();
        $this->assertInstanceOf(IntegerColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertNull($column->getLength());
        $this->assertNull($column->isNotNull());
        $this->assertFalse($column->isUnique());
        $this->assertFalse($column->isAutoIncrement());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertNull($column->getDefault());
        $this->assertNull($column->getAppend());
        $this->assertFalse($column->isUnsigned());
        $this->assertNull($column->getComment());
        $this->assertNull($column->getAfter());
        $this->assertFalse($column->isFirst());
    }

    /** @test */
    public function shouldProperlyReturnValueForAlterColumnWithNonDefaults(): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData(
            [
                'column',
                [
                    'type' => Schema::TYPE_CHAR,
                    'length' => 10,
                    'isNotNull' => true,
                    'isUnique' => true,
                    'autoIncrement' => true,
                    'isPrimaryKey' => true,
                    'default' => 'default-value',
                    'append' => 'append-value',
                    'isUnsigned' => true,
                    'comment' => 'comment-value',
                    'after' => 'after-column',
                    'isFirst' => true,
                ]
            ]
        );

        $column = $this->change->getValue();
        $this->assertInstanceOf(CharacterColumn::class, $column);
        $this->assertSame('column', $column->getName());
        $this->assertSame(10, $column->getLength());
        $this->assertTrue($column->isNotNull());
        $this->assertTrue($column->isUnique());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertSame('default-value', $column->getDefault());
        $this->assertSame('append-value', $column->getAppend());
        $this->assertTrue($column->isUnsigned());
        $this->assertSame('comment-value', $column->getComment());
        $this->assertSame('after-column', $column->getAfter());
        $this->assertTrue($column->isFirst());
    }

    public function providerForWrongRenameColumn(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1]],
            [['a', 2]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongRenameColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForWrongDataForRenameColumn($data): void
    {
        $this->change->setMethod('renameColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForRenameColumn(): void
    {
        $this->change->setMethod('renameColumn');
        $this->change->setData(['a', 'b']);

        $this->assertSame(['old' => 'a', 'new' => 'b'], $this->change->getValue());
    }

    public function providerForWrongAddPrimaryKey(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, []]],
            [['col', 1]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongRenameColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForAddPrimaryKeyWithWrongData($data): void
    {
        $this->change->setMethod('addPrimaryKey');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForAddPrimaryKey(): void
    {
        $this->change->setMethod('addPrimaryKey');
        $this->change->setData(['pk', ['column']]);

        /** @var PrimaryKeyInterface $primaryKey */
        $primaryKey = $this->change->getValue();
        $this->assertInstanceOf(PrimaryKeyInterface::class, $primaryKey);
        $this->assertSame('pk', $primaryKey->getName());
        $this->assertSame(['column'], $primaryKey->getColumns());
    }

    public function providerForWrongAddForeignKey(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, [], 'a', []]],
            [['fk', 1, 'a', []]],
            [['fk', [], 1, []]],
            [['fk', [], 'tab2', 1]]
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongRenameColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForAddForeignKeyWithWrongData($data): void
    {
        $this->change->setMethod('addForeignKey');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForAddForeignKey(): void
    {
        $this->change->setMethod('addForeignKey');
        $this->change->setData(['fk', ['column'], 'tab', ['column']]);

        /** @var ForeignKeyInterface $foreignKey */
        $foreignKey = $this->change->getValue();
        $this->assertInstanceOf(ForeignKeyInterface::class, $foreignKey);
        $this->assertSame('fk', $foreignKey->getName());
        $this->assertSame(['column'], $foreignKey->getColumns());
        $this->assertSame('tab', $foreignKey->getReferencedTable());
        $this->assertSame(['column'], $foreignKey->getReferencedColumns());
    }

    public function providerForWrongCreateIndex(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, [], true]],
            [['fk', 1, true]],
            [['fk', [], 1]],
        ];
    }

    /**
     * @test
     * @dataProvider providerForWrongRenameColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForCreateIndexWithWrongData($data): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldProperlyReturnValueForCreateIndex(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(['idx', ['column'], true]);

        /** @var IndexInterface $index */
        $index = $this->change->getValue();
        $this->assertInstanceOf(IndexInterface::class, $index);
        $this->assertSame('idx', $index->getName());
        $this->assertSame(['column'], $index->getColumns());
        $this->assertTrue($index->isUnique());
    }

    /**
     * @test
     * @dataProvider providerForWrongRenameColumn
     * @param mixed $data
     */
    public function shouldThrowExceptionForWrongDataForAddCommentOnColumn($data): void
    {
        $this->change->setMethod('addCommentOnColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /** @test */
    public function shouldReturnProperlyValueForAddCommentOnColumn(): void
    {
        $this->change->setMethod('addCommentOnColumn');
        $this->change->setData(['column', 'comment']);

        $this->assertSame(['name' => 'column', 'comment' => 'comment'], $this->change->getValue());
    }

    public function providerForDataReturnMethods(): array
    {
        return [
            ['renameTable'],
            ['dropTable'],
            ['dropColumn'],
            ['dropPrimaryKey'],
            ['dropForeignKey'],
            ['dropIndex'],
            ['dropCommentFromColumn'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForDataReturnMethods
     * @param string $method
     */
    public function shouldProperlyReturnDataForMethods(string $method): void
    {
        $this->change->setMethod($method);
        $this->change->setData('data');

        $this->assertSame('data', $this->change->getValue());
    }
}
