<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\IntegerColumn;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\StructureBuilder;
use bizley\migration\table\StructureChangeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use yii\base\InvalidArgumentException;

/**
 * @group table
 * @group structurebuilder
 */
final class StructureBuilderTest extends TestCase
{
    /** @var StructureBuilder */
    private $builder;

    /** @var StructureChangeInterface|MockObject */
    private $change;

    protected function setUp(): void
    {
        $this->builder = new StructureBuilder();
        $this->change = $this->createMock(StructureChangeInterface::class);
    }

    /** @test */
    public function shouldThrowExceptionWhenNoStructureChange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->build([new stdClass()], null, null);
    }

    /** @test */
    public function shouldProperlyBuildWithCreateTableAndNonPKColumn(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');

        $this->change->method('getMethod')->willReturn('createTable');
        $this->change->method('getTable')->willReturn('table');
        $this->change->method('getValue')->willReturn([$column]);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertSame('table', $structure->getName());
        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
        self::assertNull($structure->getPrimaryKey());
    }

    /** @test */
    public function shouldProperlyBuildWithCreateTableAndPKColumn(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');
        $column->method('isPrimaryKey')->willReturn(true);

        $this->change->method('getMethod')->willReturn('createTable');
        $this->change->method('getTable')->willReturn('table');
        $this->change->method('getValue')->willReturn([$column]);

        $structure = $this->builder->build([$this->change], null, null);
        $structurePrimaryKey = $structure->getPrimaryKey();

        self::assertSame('table', $structure->getName());
        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
        self::assertNotNull($structurePrimaryKey);
        self::assertSame(['column'], $structurePrimaryKey->getColumns());
    }

    /** @test */
    public function shouldProperlyBuildWithCreateTableAndColumnWithPKAppended(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');
        $column->method('isPrimaryKeyInfoAppended')->willReturn(true);

        $this->change->method('getMethod')->willReturn('createTable');
        $this->change->method('getTable')->willReturn('table');
        $this->change->method('getValue')->willReturn([$column]);

        $structure = $this->builder->build([$this->change], null, null);
        $structurePrimaryKey = $structure->getPrimaryKey();

        self::assertSame('table', $structure->getName());
        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
        self::assertNotNull($structurePrimaryKey);
        self::assertSame(['column'], $structurePrimaryKey->getColumns());
    }

    /** @test */
    public function shouldProperlyBuildWithCreateTableAndPKColumnWithExistingPK(): void
    {
        $primaryKey = new PrimaryKey();
        $primaryKey->addColumn('pk-column');

        $primaryKeyChange = $this->createMock(StructureChangeInterface::class);
        $primaryKeyChange->method('getMethod')->willReturn('addPrimaryKey');
        $primaryKeyChange->method('getValue')->willReturn($primaryKey);

        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');
        $column->method('isPrimaryKey')->willReturn(true);

        $this->change->method('getMethod')->willReturn('createTable');
        $this->change->method('getTable')->willReturn('table');
        $this->change->method('getValue')->willReturn([$column]);

        $structure = $this->builder->build([$primaryKeyChange, $this->change], null, null);
        $structurePrimaryKey = $structure->getPrimaryKey();

        self::assertSame('table', $structure->getName());
        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
        self::assertNotNull($structurePrimaryKey);
        self::assertSame(['pk-column', 'column'], $structurePrimaryKey->getColumns());
    }

    /** @test */
    public function shouldProperlyBuildWithAddColumn(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');

        $this->change->method('getMethod')->willReturn('addColumn');
        $this->change->method('getValue')->willReturn($column);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
    }

    /** @test */
    public function shouldProperlyBuildWithAlterColumn(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');

        $this->change->method('getMethod')->willReturn('alterColumn');
        $this->change->method('getValue')->willReturn($column);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertCount(1, $structure->getColumns());
        self::assertNotNull($structure->getColumn('column'));
    }

    /** @test */
    public function shouldProperlyBuildWithDropColumn(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getType')->willReturn('type');
        $column->method('getName')->willReturn('column');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $this->change->method('getMethod')->willReturn('dropColumn');
        $this->change->method('getValue')->willReturn('column');

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(0, $structure->getColumns());
        self::assertNull($structure->getColumn('column'));
    }

    /** @test */
    public function shouldProperlyBuildWithRenameColumn(): void
    {
        $column = new IntegerColumn(); // no mock because of cloning
        $column->setType('integer');
        $column->setName('column');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $this->change->method('getMethod')->willReturn('renameColumn');
        $this->change->method('getValue')->willReturn(['old' => 'column', 'new' => 'new-column']);

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(1, $structure->getColumns());
        self::assertNull($structure->getColumn('column'));
        self::assertNotNull($structure->getColumn('new-column'));
    }

    /** @test */
    public function shouldProperlyBuildWithAddPrimaryKeyAndNoColumn(): void
    {
        $primaryKey = new PrimaryKey();
        $primaryKey->addColumn('pk-column');

        $this->change->method('getMethod')->willReturn('addPrimaryKey');
        $this->change->method('getValue')->willReturn($primaryKey);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertCount(0, $structure->getColumns());
        self::assertNotNull($structure->getPrimaryKey());
    }

    /** @test */
    public function shouldProperlyBuildWithAddPrimaryKeyAndMatchingColumnNothingAppended(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('column');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $primaryKey = new PrimaryKey();
        $primaryKey->addColumn('column');

        $this->change->method('getMethod')->willReturn('addPrimaryKey');
        $this->change->method('getValue')->willReturn($primaryKey);

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(1, $structure->getColumns());
        self::assertSame('PRIMARY KEY', $structure->getColumn('column')->getAppend());
    }

    /** @test */
    public function shouldProperlyBuildWithAddPrimaryKeyAndMatchingColumnWithAppend(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('column');
        $column->setAppend('appended');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $primaryKey = new PrimaryKey();
        $primaryKey->addColumn('column');

        $this->change->method('getMethod')->willReturn('addPrimaryKey');
        $this->change->method('getValue')->willReturn($primaryKey);

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(1, $structure->getColumns());
        self::assertSame('appended PRIMARY KEY', $structure->getColumn('column')->getAppend());
    }

    /** @test */
    public function shouldProperlyBuildWithDropPrimaryKey(): void
    {
        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addPrimaryKey');
        $changeAdd->method('getValue')->willReturn(new PrimaryKey());

        $this->change->method('getMethod')->willReturn('dropPrimaryKey');

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertNull($structure->getPrimaryKey());
    }

    /** @test */
    public function shouldProperlyBuildWithDropPrimaryKeyWithMatchingColumn(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('column');
        $column->setAppend('appended');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $primaryKey = new PrimaryKey();
        $primaryKey->addColumn('column');

        $changeAddPK = $this->createMock(StructureChangeInterface::class);
        $changeAddPK->method('getMethod')->willReturn('addPrimaryKey');
        $changeAddPK->method('getValue')->willReturn($primaryKey);

        $this->change->method('getMethod')->willReturn('dropPrimaryKey');

        $structure = $this->builder->build([$changeAdd, $changeAddPK, $this->change], null, null);

        self::assertNull($structure->getPrimaryKey());
        self::assertSame('appended', $structure->getColumn('column')->getAppend());
    }

    /** @test */
    public function shouldProperlyBuildWithAddForeignKey(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn('fk');

        $this->change->method('getMethod')->willReturn('addForeignKey');
        $this->change->method('getValue')->willReturn($foreignKey);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertCount(1, $structure->getForeignKeys());
        self::assertNotNull($structure->getForeignKey('fk'));
    }

    /** @test */
    public function shouldProperlyBuildWithDropForeignKey(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn('fk');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addForeignKey');
        $changeAdd->method('getValue')->willReturn($foreignKey);

        $this->change->method('getMethod')->willReturn('dropForeignKey');
        $this->change->method('getValue')->willReturn('fk');

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(0, $structure->getForeignKeys());
        self::assertNull($structure->getForeignKey('fk'));
    }

    /** @test */
    public function shouldProperlyBuildWithCreateIndexNonUnique(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('idx');

        $this->change->method('getMethod')->willReturn('createIndex');
        $this->change->method('getValue')->willReturn($index);

        $structure = $this->builder->build([$this->change], null, null);

        self::assertCount(1, $structure->getIndexes());
        self::assertNotNull($structure->getIndex('idx'));
    }

    /** @test */
    public function shouldProperlyBuildWithCreateIndexUniqueAndMatchingColumn(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('idx-column');
        $column->setUnique(false);

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('addColumn');
        $changeAdd->method('getValue')->willReturn($column);

        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn(true);
        $index->method('getColumns')->willReturn(['idx-column']);

        $this->change->method('getMethod')->willReturn('createIndex');
        $this->change->method('getValue')->willReturn($index);

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(1, $structure->getIndexes());
        self::assertNotNull($structure->getIndex('idx'));
        self::assertTrue($structure->getColumn('idx-column')->isUnique());
    }

    /** @test */
    public function shouldProperlyBuildWithDropIndexNonUnique(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('idx');

        $changeAdd = $this->createMock(StructureChangeInterface::class);
        $changeAdd->method('getMethod')->willReturn('createIndex');
        $changeAdd->method('getValue')->willReturn($index);

        $this->change->method('getMethod')->willReturn('dropIndex');
        $this->change->method('getValue')->willReturn('idx');

        $structure = $this->builder->build([$changeAdd, $this->change], null, null);

        self::assertCount(0, $structure->getIndexes());
        self::assertNull($structure->getIndex('idx'));
    }

    /** @test */
    public function shouldProperlyBuildWithDropIndexUniqueAndMatchingColumn(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('idx-column');
        $column->setUnique(false);

        $changeAddColumn = $this->createMock(StructureChangeInterface::class);
        $changeAddColumn->method('getMethod')->willReturn('addColumn');
        $changeAddColumn->method('getValue')->willReturn($column);

        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn(true);
        $index->method('getColumns')->willReturn(['idx-column']);

        $changeAddIndex = $this->createMock(StructureChangeInterface::class);
        $changeAddIndex->method('getMethod')->willReturn('createIndex');
        $changeAddIndex->method('getValue')->willReturn($index);

        $this->change->method('getMethod')->willReturn('dropIndex');
        $this->change->method('getValue')->willReturn('idx');

        $structure = $this->builder->build([$changeAddColumn, $changeAddIndex, $this->change], null, null);

        self::assertCount(0, $structure->getIndexes());
        self::assertNull($structure->getIndex('idx'));
        self::assertFalse($structure->getColumn('idx-column')->isUnique());
    }

    /** @test */
    public function shouldProperlyBuildWithAddCommentOnColumn(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('column');

        $changeAddColumn = $this->createMock(StructureChangeInterface::class);
        $changeAddColumn->method('getMethod')->willReturn('addColumn');
        $changeAddColumn->method('getValue')->willReturn($column);

        $this->change->method('getMethod')->willReturn('addCommentOnColumn');
        $this->change->method('getValue')->willReturn(
            [
                'column' => 'column',
                'comment' => 'comment-to-add'
            ]
        );

        $structure = $this->builder->build([$changeAddColumn, $this->change], null, null);

        self::assertSame('comment-to-add', $structure->getColumn('column')->getComment());
    }

    /** @test */
    public function shouldProperlyBuildWithDropCommentFromColumn(): void
    {
        $column = new IntegerColumn();
        $column->setType('integer');
        $column->setName('column');
        $column->setComment('comment-to-drop');

        $changeAddColumn = $this->createMock(StructureChangeInterface::class);
        $changeAddColumn->method('getMethod')->willReturn('addColumn');
        $changeAddColumn->method('getValue')->willReturn($column);

        $this->change->method('getMethod')->willReturn('dropCommentFromColumn');
        $this->change->method('getValue')->willReturn('column');

        $structure = $this->builder->build([$changeAddColumn, $this->change], null, null);

        self::assertNull($structure->getColumn('column')->getComment());
    }
}
