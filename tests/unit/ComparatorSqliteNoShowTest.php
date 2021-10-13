<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Comparator;
use bizley\migration\Schema;
use bizley\migration\table\Index;
use bizley\migration\table\PrimaryKey;
use yii\base\NotSupportedException;

/** @group comparator */
final class ComparatorSqliteNoShowTest extends ComparatorNonSqliteTest
{
    /**
     * @param bool $generalSchema
     */
    public function compare(bool $generalSchema = true): void
    {
        (new Comparator($generalSchema))->compare(
            $this->newStructure,
            $this->oldStructure,
            $this->blueprint,
            false,
            Schema::SQLITE,
            null
        );
    }

    /**
     * @test
     */
    public function shouldDropColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $column1 = $this->getColumn('col1');
        $this->newStructure->method('getColumns')->willReturn([]);
        $this->oldStructure->method('getColumns')->willReturn(['col1' => $column1]);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetType(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setType('a');
        $columnOld = $this->getColumn('col');
        $columnOld->setType('b');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForIsNotNull(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setNotNull(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setNotNull(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetLength(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setLength(10);
        $columnOld = $this->getColumn('col');
        $columnOld->setLength(2);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetLengthDecimal(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setLength('10, 2');
        $columnOld = $this->getColumn('col');
        $columnOld->setLength('9, 3');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForIsUnique(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setUnique(false);
        $columnOld = $this->getColumn('col');
        $columnOld->setUnique(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForIsUniqueWhenIndexIsMulticolumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setUnique(false);
        $columnOld = $this->getColumn('col');
        $columnOld->setUnique(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);
        $index = $this->getIndex('idx');
        $index->setUnique(true);
        $index->setColumns(['col', 'col2']);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->oldStructure->method('getIndex')->willReturn($index);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->newStructure->method('getIndex')->willReturn($index);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForIsUnsigned(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setUnsigned(false);
        $columnOld = $this->getColumn('col');
        $columnOld->setUnsigned(true);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetDefault(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setDefault(['a']);
        $columnOld = $this->getColumn('col');
        $columnOld->setDefault(['b']);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetDefaultNULL(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setDefault('NULL');
        $columnOld = $this->getColumn('col');
        $columnOld->setDefault(null);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        self::assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetComment(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setComment('abc');
        $columnOld = $this->getColumn('col');
        $columnOld->setComment('def');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendNonPK(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('abc');
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('def');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendWithPKAndNonGeneralSchema(): void
    {
        $this->expectException(NotSupportedException::class);

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
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendWithNotPKAndEmptyOldAppend(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendWithNotAutoIncrementVariant1AndEmptyOldAppend(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTO_INCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendWithNotAutoIncrementVariant2AndEmptyOldAppend(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTOINCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAlterColumnForGetAppendWithNoIdentityAndEmptyOldAppend(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('IDENTITY PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(false);
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAddForeignKeys(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKey = $this->getForeignKey('fk');
        $foreignKey2 = $this->getForeignKey('fk2');
        $this->newStructure->method('getForeignKeys')->willReturnOnConsecutiveCalls(
            [
                'fk' => $foreignKey,
                'fk2' => $foreignKey2,
            ]
        );
        $this->oldStructure->method('getForeignKeys')->willReturn([]);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldDropForeignKey(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKey = $this->getForeignKey('fk');
        $this->newStructure->method('getForeignKeys')->willReturn([]);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeysWithDifferentColumns(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setColumns(['a', 'b', 'd']);
        $foreignKeyNew2 = $this->getForeignKey('fk2');
        $foreignKeyNew2->setColumns(['a', 'b']);
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setColumns(['a', 'c']);
        $foreignKeyOld2 = $this->getForeignKey('fk2');
        $foreignKeyOld2->setColumns(['d', 'e']);
        $this->newStructure->method('getForeignKeys')->willReturn(
            [
                'fk' => $foreignKeyNew,
                'fk2' => $foreignKeyNew2,
            ]
        );
        $this->newStructure
            ->method('getForeignKey')
            ->willReturnOnConsecutiveCalls($foreignKeyNew, $foreignKeyNew2);
        $this->oldStructure->method('getForeignKeys')->willReturn(
            [
                'fk' => $foreignKeyOld,
                'fk2' => $foreignKeyOld2,
            ]
        );
        $this->oldStructure
            ->method('getForeignKey')
            ->willReturnOnConsecutiveCalls($foreignKeyOld, $foreignKeyOld2);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeyWithDifferentReferredColumns(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setReferredColumns(['a', 'b']);
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setReferredColumns(['c', 'b']);
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeyWithDifferentReferredTable(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setReferredTable('a');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setReferredTable('b');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeyWithDifferentOnUpdateConstraint(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setOnUpdate('CASCADE');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setOnUpdate('RESTRICT');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeyWithDifferentOnDeleteConstraint(): void
    {
        $this->expectException(NotSupportedException::class);

        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setOnDelete('CASCADE');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setOnDelete('RESTRICT');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplacePrimaryKeyWhenOnlyNewOne(): void
    {
        $this->expectException(NotSupportedException::class);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['a']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplacePrimaryKeyWhenOnlyOldOne(): void
    {
        $this->expectException(NotSupportedException::class);

        $primaryKeyOld = new PrimaryKey();
        $primaryKeyOld->setColumns(['a']);
        $this->newStructure->method('getPrimaryKey')->willReturn(null);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplacePrimaryKeyWhenDifferentColumns(): void
    {
        $this->expectException(NotSupportedException::class);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['a']);
        $primaryKeyOld = new PrimaryKey();
        $primaryKeyOld->setColumns(['b']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn($primaryKeyOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldNotReplacePrimaryKeyWhenPKInfoAlteredAlready(): void
    {
        $this->expectException(NotSupportedException::class);

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
    }

    /**
     * @test
     */
    public function shouldReplacePrimaryKeyWhenPKInfoAddedAlreadyAndRemoveExcessive(): void
    {
        $this->expectException(NotSupportedException::class);

        $column = $this->getColumn('col');
        $column->setAppend('abc PRIMARY KEY');
        $this->newStructure->method('getColumns')->willReturn(['col' => $column]);
        $this->newStructure->method('getColumn')->willReturn($column);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col', 'col2']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldReplacePrimaryKeyWhenPKInfoAlteredAlreadyAndRemoveExcessive(): void
    {
        $this->expectException(NotSupportedException::class);

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
    }

    /**
     * @test
     */
    public function shouldGoThroughAllTheColumnProperties(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAutoIncrement(true);
        $columnNew->setLength('10,0');
        $columnNew->setUnique(false);
        $columnNew->setDefault('NULL');

        $columnOld = $this->getColumn('col');
        $columnOld->setLength(10);
        $columnOld->setAppend('AUTOINCREMENT');
        $columnOld->setUnique(true);
        $columnOld->setDefault(null);

        $index = new Index();
        $index->setColumns(['col']);
        $index->setUnique(true);

        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getIndexes')->willReturn([]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->oldStructure->expects(self::exactly(8))->method('getColumn')->willReturn($columnOld);

        $this->compare();
    }

    /**
     * @test
     */
    public function shouldAddPrimaryKeyWhenPKInfoNotAlteredAlready(): void
    {
        $this->expectException(NotSupportedException::class);

        $columnNew = $this->getColumn('col');
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
    }

    /**
     * @test
     */
    public function shouldAddPrimaryKeyWhenPKInfoNotAddedAlready(): void
    {
        $this->expectException(NotSupportedException::class);

        $column = $this->getColumn('col');
        $this->newStructure->method('getColumns')->willReturn(['col' => $column]);
        $this->newStructure->method('getColumn')->willReturn($column);

        $primaryKeyNew = new PrimaryKey();
        $primaryKeyNew->setColumns(['col']);
        $this->newStructure->method('getPrimaryKey')->willReturn($primaryKeyNew);
        $this->oldStructure->method('getPrimaryKey')->willReturn(null);

        $this->compare();
    }
}
