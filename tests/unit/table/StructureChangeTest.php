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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\db\Schema;

#[Group('table')]
#[Group('structurechange')]
final class StructureChangeTest extends TestCase
{
    /** @var StructureChange */
    private $change;

    protected function setUp(): void
    {
        $this->change = new StructureChange();
    }

    #[Test]
    public function shouldProperlySetTable(): void
    {
        $this->change->setTable('test');
        self::assertSame('test', $this->change->getTable());
    }

    #[Test]
    public function shouldReturnDataForUnknownMethod(): void
    {
        $this->change->setMethod('unknown');
        $this->change->setData('data');

        self::assertSame('data', $this->change->getValue());
    }

    /**
     * @return list<array{mixed}>
     */
    public static function providerForWrongCreateTable(): array
    {
        return [
            ['wrong'],
            [['column' => []]],
        ];
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongCreateTable')]
    #[Test]
    public function shouldThrowExceptionForWrongDataForCreateTable($data): void
    {
        $this->change->setMethod('createTable');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForCreateTableWithDefaults(): void
    {
        $this->change->setMethod('createTable');
        $this->change->setData(['column' => ['type' => Schema::TYPE_INTEGER]]);

        $columns = $this->change->getValue();
        self::assertCount(1, $columns);
        /** @var ColumnInterface $column */
        $column = $columns[0];
        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertNull($column->getLength());
        self::assertFalse($column->isNotNull());
        self::assertFalse($column->isUnique());
        self::assertFalse($column->isAutoIncrement());
        self::assertFalse($column->isPrimaryKey());
        self::assertNull($column->getDefault());
        self::assertNull($column->getAppend());
        self::assertFalse($column->isUnsigned());
        self::assertNull($column->getComment());
        self::assertNull($column->getAfter());
        self::assertFalse($column->isFirst());
    }

    #[Test]
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
        self::assertCount(1, $columns);
        /** @var ColumnInterface $column */
        $column = $columns[0];
        self::assertInstanceOf(CharacterColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertSame(10, $column->getLength());
        self::assertTrue($column->isNotNull());
        self::assertTrue($column->isUnique());
        self::assertTrue($column->isAutoIncrement());
        self::assertTrue($column->isPrimaryKey());
        self::assertSame('default-value', $column->getDefault());
        self::assertSame('append-value', $column->getAppend());
        self::assertTrue($column->isUnsigned());
        self::assertSame('comment-value', $column->getComment());
        self::assertSame('after-column', $column->getAfter());
        self::assertTrue($column->isFirst());
    }

    /**
     * @return array<array-key, array{mixed}>
     */
    public static function providerForWrongAddColumn(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, []]],
            [['col', 1]],
            'no name' => [['schema' => []]],
            'no schema' => [['name' => 'col']],
            'non-string name' => [['name' => 1, 'schema' => []]],
            'non-string name with type' => [['name' => 1, 'schema' => ['type' => 'string']]],
            'non-array schema' => [['name' => 'col', 'schema' => 'string']],
        ];
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongAddColumn')]
    #[Test]
    public function shouldThrowExceptionForAddColumnWithWrongData($data): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForAddColumnWithDefaults(): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData(
            [
                'name' => 'column',
                'schema' => ['type' => Schema::TYPE_INTEGER]
            ]
        );

        $column = $this->change->getValue();
        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertNull($column->getLength());
        self::assertFalse($column->isNotNull());
        self::assertFalse($column->isUnique());
        self::assertFalse($column->isAutoIncrement());
        self::assertFalse($column->isPrimaryKey());
        self::assertNull($column->getDefault());
        self::assertNull($column->getAppend());
        self::assertFalse($column->isUnsigned());
        self::assertNull($column->getComment());
        self::assertNull($column->getAfter());
        self::assertFalse($column->isFirst());
    }

    #[Test]
    public function shouldProperlyReturnValueForAddColumnWithNonDefaults(): void
    {
        $this->change->setMethod('addColumn');
        $this->change->setData(
            [
                'name' => 'column',
                'schema' => [
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
        self::assertInstanceOf(CharacterColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertSame(10, $column->getLength());
        self::assertTrue($column->isNotNull());
        self::assertTrue($column->isUnique());
        self::assertTrue($column->isAutoIncrement());
        self::assertTrue($column->isPrimaryKey());
        self::assertSame('default-value', $column->getDefault());
        self::assertSame('append-value', $column->getAppend());
        self::assertTrue($column->isUnsigned());
        self::assertSame('comment-value', $column->getComment());
        self::assertSame('after-column', $column->getAfter());
        self::assertTrue($column->isFirst());
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongAddColumn')]
    #[Test]
    public function shouldThrowExceptionForAlterColumnWithWrongData($data): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForAlterColumnWithDefaults(): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData(
            [
                'name' => 'column',
                'schema' => ['type' => Schema::TYPE_INTEGER]
            ]
        );

        $column = $this->change->getValue();
        self::assertInstanceOf(IntegerColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertNull($column->getLength());
        self::assertFalse($column->isNotNull());
        self::assertFalse($column->isUnique());
        self::assertFalse($column->isAutoIncrement());
        self::assertFalse($column->isPrimaryKey());
        self::assertNull($column->getDefault());
        self::assertNull($column->getAppend());
        self::assertFalse($column->isUnsigned());
        self::assertNull($column->getComment());
        self::assertNull($column->getAfter());
        self::assertFalse($column->isFirst());
    }

    #[Test]
    public function shouldProperlyReturnValueForAlterColumnWithNonDefaults(): void
    {
        $this->change->setMethod('alterColumn');
        $this->change->setData(
            [
                'name' => 'column',
                'schema' => [
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
        self::assertInstanceOf(CharacterColumn::class, $column);
        self::assertSame('column', $column->getName());
        self::assertSame(10, $column->getLength());
        self::assertTrue($column->isNotNull());
        self::assertTrue($column->isUnique());
        self::assertTrue($column->isAutoIncrement());
        self::assertTrue($column->isPrimaryKey());
        self::assertSame('default-value', $column->getDefault());
        self::assertSame('append-value', $column->getAppend());
        self::assertTrue($column->isUnsigned());
        self::assertSame('comment-value', $column->getComment());
        self::assertSame('after-column', $column->getAfter());
        self::assertTrue($column->isFirst());
    }

    /**
     * @return array<array-key, array{mixed}>
     */
    public static function providerForWrongRenameColumn(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1]],
            [['a', 2]],
            'no new' => [['old' => 'a']],
            'no old' => [['new' => 'a']],
            'non-string new' => [['new' => 1, 'old' => 'a']],
            'non-string old' => [['new' => 'a', 'old' => 1]],
        ];
    }

    /**
     * @return array<array-key, array{mixed}>
     */
    public static function providerForWrongAddCommentOnColumn(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1]],
            [['a', 2]],
            'no column' => [['comment' => 'a']],
            'no comment' => [['column' => 'a']],
            'non-string column' => [['column' => 1, 'comment' => 'a']],
            'non-string comment' => [['column' => 'a', 'comment' => 1]],
        ];
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongRenameColumn')]
    #[Test]
    public function shouldThrowExceptionForWrongDataForRenameColumn($data): void
    {
        $this->change->setMethod('renameColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForRenameColumn(): void
    {
        $this->change->setMethod('renameColumn');
        $this->change->setData(
            [
                'old' => 'a',
                'new' => 'b'
            ]
        );

        self::assertSame(['old' => 'a', 'new' => 'b'], $this->change->getValue());
    }

    /**
     * @return list<array{mixed}>
     */
    public static function providerForWrongAddPrimaryKey(): array
    {
        return [
            ['wrong'],
            [[]],
            [[1, []]],
            [['col', 1]],
        ];
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongAddPrimaryKey')]
    #[Test]
    public function shouldThrowExceptionForAddPrimaryKeyWithWrongData($data): void
    {
        $this->change->setMethod('addPrimaryKey');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForAddPrimaryKey(): void
    {
        $this->change->setMethod('addPrimaryKey');
        $this->change->setData(
            [
                'name' => 'pk',
                'columns' => ['column']
            ]
        );

        $primaryKey = $this->change->getValue();
        self::assertInstanceOf(PrimaryKeyInterface::class, $primaryKey);
        self::assertSame('pk', $primaryKey->getName());
        self::assertSame(['column'], $primaryKey->getColumns());
    }

    /**
     * @return list<array{mixed}>
     */
    public static function providerForWrongAddForeignKey(): array
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
     * @param mixed $data
     */
    #[DataProvider('providerForWrongAddForeignKey')]
    #[Test]
    public function shouldThrowExceptionForAddForeignKeyWithWrongData($data): void
    {
        $this->change->setMethod('addForeignKey');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForAddForeignKey(): void
    {
        $this->change->setMethod('addForeignKey');
        $this->change->setData(
            [
                'name' => 'fk',
                'columns' => ['column'],
                'referredTable' => 'tab',
                'referredColumns' => ['column'],
                'onDelete' => 'CASCADE',
                'onUpdate' => 'RESTRICT',
                'tableName' => 'test'
            ]
        );

        $foreignKey = $this->change->getValue();
        self::assertInstanceOf(ForeignKeyInterface::class, $foreignKey);
        self::assertSame('fk', $foreignKey->getName());
        self::assertSame(['column'], $foreignKey->getColumns());
        self::assertSame('tab', $foreignKey->getReferredTable());
        self::assertSame(['column'], $foreignKey->getReferredColumns());
        self::assertSame('CASCADE', $foreignKey->getOnDelete());
        self::assertSame('RESTRICT', $foreignKey->getOnUpdate());
        self::assertSame('test', $foreignKey->getTableName());
    }

    /**
     * @return list<array{mixed}>
     */
    public static function providerForWrongCreateIndex(): array
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
     * @param mixed $data
     */
    #[DataProvider('providerForWrongCreateIndex')]
    #[Test]
    public function shouldThrowExceptionForCreateIndexWithWrongData($data): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldProperlyReturnValueForCreateIndex(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(
            [
                'name' => 'idx',
                'columns' => ['column'],
                'unique' => true,
            ]
        );

        $index = $this->change->getValue();
        self::assertInstanceOf(IndexInterface::class, $index);
        self::assertSame('idx', $index->getName());
        self::assertSame(['column'], $index->getColumns());
        self::assertTrue($index->isUnique());
        self::assertNull($index->getMethod());
        self::assertNull($index->getDefinition());
    }

    #[Test]
    public function shouldProperlyReturnValueForCreateIndexWithMethod(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(
            [
                'name' => 'idx',
                'columns' => ['column'],
                'unique' => false,
                'method' => 'hash',
            ]
        );

        $index = $this->change->getValue();
        self::assertInstanceOf(IndexInterface::class, $index);
        self::assertFalse($index->isUnique());
        self::assertSame('hash', $index->getMethod());
    }

    #[Test]
    public function shouldProperlyReturnValueForCreateIndexWithDefinition(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(
            [
                'name' => 'idx',
                'columns' => [],
                'unique' => true,
                'definition' => 'USING btree (a) WHERE (a > 0)',
            ]
        );

        $index = $this->change->getValue();
        self::assertInstanceOf(IndexInterface::class, $index);
        self::assertTrue($index->isUnique());
        self::assertSame([], $index->getColumns());
        self::assertNull($index->getMethod());
        self::assertSame('USING btree (a) WHERE (a > 0)', $index->getDefinition());
    }

    #[Test]
    public function shouldThrowExceptionForCreateIndexWithWrongDefinition(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(['name' => 'idx', 'columns' => [], 'unique' => false, 'definition' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldThrowExceptionForCreateIndexWithWrongMethod(): void
    {
        $this->change->setMethod('createIndex');
        $this->change->setData(['name' => 'idx', 'columns' => ['column'], 'unique' => false, 'method' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    /**
     * @param mixed $data
     */
    #[DataProvider('providerForWrongAddCommentOnColumn')]
    #[Test]
    public function shouldThrowExceptionForWrongDataForAddCommentOnColumn($data): void
    {
        $this->change->setMethod('addCommentOnColumn');
        $this->change->setData($data);

        $this->expectException(InvalidArgumentException::class);
        $this->change->getValue();
    }

    #[Test]
    public function shouldReturnProperlyValueForAddCommentOnColumn(): void
    {
        $this->change->setMethod('addCommentOnColumn');
        $this->change->setData(
            [
                'column' => 'column',
                'comment' => 'comment',
            ]
        );

        self::assertSame(['column' => 'column', 'comment' => 'comment'], $this->change->getValue());
    }

    /**
     * @return list<array{string}>
     */
    public static function providerForDataReturnMethods(): array
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
     * @param string $method
     */
    #[DataProvider('providerForDataReturnMethods')]
    #[Test]
    public function shouldProperlyReturnDataForMethods(string $method): void
    {
        $this->change->setMethod($method);
        $this->change->setData('data');

        self::assertSame('data', $this->change->getValue());
    }
}
