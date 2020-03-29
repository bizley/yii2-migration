<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Comparator;
use bizley\migration\table\Blueprint;
use bizley\migration\table\CharacterColumn;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\Index;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\StructureInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;

use function array_keys;

class ComparatorNonSqliteTest extends TestCase
{
    /** @var StructureInterface|MockObject */
    public $newStructure;

    /** @var StructureInterface|MockObject */
    public $oldStructure;

    /** @var Blueprint */
    public $blueprint;

    protected function setUp(): void
    {
        $this->newStructure = $this->createMock(StructureInterface::class);
        $this->oldStructure = $this->createMock(StructureInterface::class);
        $this->blueprint = new Blueprint();
    }

    public function getComparator(bool $generalSchema = true): Comparator
    {
        return new Comparator($generalSchema);
    }

    public function getColumn(string $name): CharacterColumn
    {
        $column = new CharacterColumn();
        $column->setName($name);
        $column->setType('type');
        return $column;
    }

    public function getForeignKey(string $name): ForeignKey
    {
        $foreignKey = new ForeignKey();
        $foreignKey->setName($name);
        $foreignKey->setReferredTable('table');
        return $foreignKey;
    }

    public function getIndex(string $name): Index
    {
        $index = new Index();
        $index->setName($name);
        return $index;
    }

    /**
     * @param bool $generalSchema
     * @throws NotSupportedException
     */
    public function compare(bool $generalSchema = true): void
    {
        (new Comparator($generalSchema))->compare(
            $this->newStructure,
            $this->oldStructure,
            $this->blueprint,
            true,
            null,
            null
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAddColumns(): void
    {
        $column1 = $this->getColumn('col1');
        $column2 = $this->getColumn('col2');
        $this->newStructure->method('getColumns')->willReturn(
            [
                'col1' => $column1,
                'col2' => $column2,
            ]
        );
        $this->oldStructure->method('getColumns')->willReturn([]);

        $this->compare();

        $this->assertTrue($column1->isFirst());
        $this->assertNull($column1->getAfter());
        $this->assertFalse($column2->isFirst());
        $this->assertSame('col1', $column2->getAfter());
        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            [
                "missing column 'col1'",
                "missing column 'col2'",
            ],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col1', 'col2'], array_keys($this->blueprint->getAddedColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldDropColumn(): void
    {
        $column1 = $this->getColumn('col1');
        $this->newStructure->method('getColumns')->willReturn([]);
        $this->oldStructure->method('getColumns')->willReturn(['col1' => $column1]);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(["excessive column 'col1'"], $this->blueprint->getDescriptions());
        $this->assertSame(['col1'], array_keys($this->blueprint->getDroppedColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetType(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setType('a');
        $columnOld = $this->getColumn('col');
        $columnOld->setType('b');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: type (DB: \"a\" != MIG: \"b\")"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForIsNotNull(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setNotNull(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setNotNull(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: not null (DB: TRUE != MIG: FALSE)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetLength(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setLength(10);
        $columnOld = $this->getColumn('col');
        $columnOld->setLength(2);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: length (DB: \"10\" != MIG: \"2\")"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForIsUnique(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setUnique(false);
        $columnOld = $this->getColumn('col');
        $columnOld->setUnique(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: unique (DB: FALSE != MIG: TRUE)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForIsUnsigned(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setUnsigned(false);
        $columnOld = $this->getColumn('col');
        $columnOld->setUnsigned(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: unsigned (DB: FALSE != MIG: TRUE)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetDefault(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setDefault(['a']);
        $columnOld = $this->getColumn('col');
        $columnOld->setDefault(['b']);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: default (DB: [\"a\"] != MIG: [\"b\"])"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetComment(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setComment('abc');
        $columnOld = $this->getColumn('col');
        $columnOld->setComment('def');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: comment (DB: \"abc\" != MIG: \"def\")"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendNonPK(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('abc');
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('def');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"abc\" != MIG: \"def\")"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithPKAndGeneralSchema(): void
    {
        $primaryKey = new PrimaryKey();
        $primaryKey->setColumns(['col']);
        $columnNew = $this->getColumn('col');
        $columnOld = $this->getColumn('col');
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKey);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKey);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithPKAndNonGeneralSchema(): void
    {
        $primaryKey = new PrimaryKey();
        $primaryKey->setColumns(['col']);
        $columnNew = $this->getColumn('col');
        $columnOld = $this->getColumn('col');
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKey);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKey);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare(false);

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotAlterColumnForGetAppendWithPKAndEmptyOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithNotPKAndEmptyOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithNotAutoIncrementVariant1AndEmptyOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTO_INCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"AUTO_INCREMENT\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithNotAutoIncrementVariant2AndEmptyOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTOINCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"AUTOINCREMENT\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetAppendWithNoIdentityAndEmptyOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('IDENTITY PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different 'col' column property: append (DB: \"IDENTITY PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        $this->assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAddForeignKey(): void
    {
        $foreignKey = $this->getForeignKey('fk');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);
        $this->oldStructure->method('getForeignKeys')->willReturn([]);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["missing foreign key 'fk'"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldDropForeignKey(): void
    {
        $foreignKey = $this->getForeignKey('fk');
        $this->newStructure->method('getForeignKeys')->willReturn([]);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["excessive foreign key 'fk'"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeyWithDifferentColumns(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setColumns(['a', 'b']);
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setColumns(['a', 'c']);
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different foreign key 'fk' columns (DB: [\"a\",\"b\"] != MIG: [\"a\",\"c\"])"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        $this->assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeyWithDifferentReferredColumns(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setReferredColumns(['a', 'b']);
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setReferredColumns(['c', 'b']);
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different foreign key 'fk' referred columns (DB: [\"a\",\"b\"] != MIG: [\"c\",\"b\"])"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        $this->assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeyWithDifferentReferredTable(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setReferredTable('a');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setReferredTable('b');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different foreign key 'fk' referred table (DB: \"a\" != MIG: \"b\")"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        $this->assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplacePrimaryKeyWhenOnlyNewOne(): void
    {
        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['a']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertNull($this->blueprint->getTableOldPrimaryKey());
        $this->assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplacePrimaryKeyWhenOnlyOldOne(): void
    {
        $primaryKeyOld = new PrimaryKey();
        $primaryKeyOld->setColumns(['a']);
        $this->newStructure->method('getPrimaryKey')->willReturn(null);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKeyOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        $this->assertNull($this->blueprint->getTableNewPrimaryKey());
        $this->assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        $this->assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplacePrimaryKeyWhenDifferentColumns(): void
    {
        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['a']);
        $primaryKeyOld = new PrimaryKey();
        $primaryKeyOld->setColumns(['b']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKeyOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        $this->assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        $this->assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotReplacePrimaryKeyWhenPKInfoAddedAlready(): void
    {
        $column = $this->getColumn('col');
        $column->setAppend('PRIMARY KEY');
        $this->newStructure->method('getColumns')->willReturn(['col' => $column]);
        $this->newStructure->method('getColumn')->willReturn($column);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertNull($this->blueprint->getTableOldPrimaryKey());
        $this->assertNull($this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldNotReplacePrimaryKeyWhenPKInfoAlteredAlready(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('abc');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertNull($this->blueprint->getTableOldPrimaryKey());
        $this->assertNull($this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplacePrimaryKeyWhenPKInfoAddedAlreadyAndRemoveExcessive(): void
    {
        $column = $this->getColumn('col');
        $column->setAppend('abc PRIMARY KEY');
        $this->newStructure->method('getColumns')->willReturn(['col' => $column]);
        $this->newStructure->method('getColumn')->willReturn($column);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col', 'col2']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertNull($this->blueprint->getTableOldPrimaryKey());
        $this->assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        $this->assertSame('abc', $column->getAppend());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplacePrimaryKeyWhenPKInfoAlteredAlreadyAndRemoveExcessive(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('abc PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('abc');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col', 'col2']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        $this->assertNull($this->blueprint->getTableOldPrimaryKey());
        $this->assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        $this->assertSame('abc', $columnNew->getAppend());
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAddIndex(): void
    {
        $index = $this->getIndex('idx');
        $index->setColumns(['a']);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->oldStructure->method('getIndexes')->willReturn([]);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["missing index 'idx'"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['idx'], array_keys($this->blueprint->getAddedIndexes()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldDropIndex(): void
    {
        $index = $this->getIndex('idx');
        $index->setColumns(['a']);
        $this->newStructure->method('getIndexes')->willReturn([]);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $index]);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["excessive index 'idx'"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['idx'], array_keys($this->blueprint->getDroppedIndexes()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceIndexWhenDifferentUnique(): void
    {
        $indexNew = $this->getIndex('idx');
        $indexNew->setColumns(['a']);
        $indexNew->setUnique(false);
        $indexOld = $this->getIndex('idx');
        $indexOld->setColumns(['a']);
        $indexOld->setUnique(true);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $indexNew]);
        $this->newStructure->method('getIndex')->willReturn($indexNew);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $indexOld]);
        $this->oldStructure->method('getIndex')->willReturn($indexOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different index 'idx' definition (DB: unique FALSE != MIG: unique TRUE)"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['idx'], array_keys($this->blueprint->getAddedIndexes()));
        $this->assertSame(['idx'], array_keys($this->blueprint->getDroppedIndexes()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceIndexWhenDifferentColumns(): void
    {
        $indexNew = $this->getIndex('idx');
        $indexNew->setColumns(['a']);
        $indexOld = $this->getIndex('idx');
        $indexOld->setColumns(['b']);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $indexNew]);
        $this->newStructure->method('getIndex')->willReturn($indexNew);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $indexOld]);
        $this->oldStructure->method('getIndex')->willReturn($indexOld);

        $this->compare();

        $this->assertTrue($this->blueprint->isPending());
        $this->assertSame(
            ["different index 'idx' columns (DB: [\"a\"]) != MIG: ([\"b\"]))"],
            $this->blueprint->getDescriptions()
        );
        $this->assertSame(['idx'], array_keys($this->blueprint->getAddedIndexes()));
        $this->assertSame(['idx'], array_keys($this->blueprint->getDroppedIndexes()));
    }
}
