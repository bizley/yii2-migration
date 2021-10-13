<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Comparator;
use bizley\migration\Schema;
use bizley\migration\table\PrimaryKey;
use yii\base\NotSupportedException;

/** @group comparator */
final class ComparatorSqliteShowTest extends ComparatorNonSqliteTest
{
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
            Schema::SQLITE,
            null
        );
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "excessive column 'col1'",
                '(!) DROP COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col1'], array_keys($this->blueprint->getDroppedColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: type (DB: \"a\" != MIG: \"b\")",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: not null (DB: TRUE != MIG: FALSE)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: length (DB: \"10\" != MIG: \"2\")",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAlterColumnForGetLengthDecimal(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setLength('10, 2');
        $columnOld = $this->getColumn('col');
        $columnOld->setLength('9, 3');
        $this->newStructure->method('getColumns')->willReturn(['col' => $columnNew]);
        $this->newStructure->method('getColumn')->willReturn($columnNew);
        $this->oldStructure->method('getColumns')->willReturn(['col' => $columnOld]);
        $this->oldStructure->method('getColumn')->willReturn($columnOld);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: length (DB: \"10, 2\" != MIG: \"9, 3\")",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: unique (DB: FALSE != MIG: TRUE)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: unsigned (DB: FALSE != MIG: TRUE)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: default (DB: [\"a\"] != MIG: [\"b\"])",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: comment (DB: \"abc\" != MIG: \"def\")",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"abc\" != MIG: \"def\")",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        $this->compare(false);

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"AUTO_INCREMENT\" != MIG: NULL)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"AUTOINCREMENT\" != MIG: NULL)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different 'col' column property: append (DB: \"IDENTITY PRIMARY KEY\" != MIG: NULL)",
                '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldAddForeignKeys(): void
    {
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "missing foreign key 'fk'",
                '(!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually',
                "missing foreign key 'fk2'",
                '(!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually',
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk', 'fk2'], array_keys($this->blueprint->getAddedForeignKeys()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "excessive foreign key 'fk'",
                '(!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeysWithDifferentColumns(): void
    {
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different foreign key 'fk' columns (DB: [\"a\",\"b\",\"d\"] != MIG: [\"a\",\"c\"])",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually',
                "different foreign key 'fk2' columns (DB: [\"a\",\"b\"] != MIG: [\"d\",\"e\"])",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually',
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk', 'fk2'], array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk', 'fk2'], array_keys($this->blueprint->getDroppedForeignKeys()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different foreign key 'fk' referred columns (DB: [\"a\",\"b\"] != MIG: [\"c\",\"b\"])",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different foreign key 'fk' referred table (DB: \"a\" != MIG: \"b\")",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeyWithDifferentOnUpdateConstraint(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setOnUpdate('CASCADE');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setOnUpdate('RESTRICT');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different foreign key 'fk' ON UPDATE constraint (DB: \"CASCADE\" != MIG: \"RESTRICT\")",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReplaceForeignKeyWithDifferentOnDeleteConstraint(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setOnDelete('CASCADE');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setOnDelete('RESTRICT');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different foreign key 'fk' ON DELETE constraint (DB: \"CASCADE\" != MIG: \"RESTRICT\")",
                '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], array_keys($this->blueprint->getDroppedForeignKeys()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                'different primary key definition',
                '(!) ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                'different primary key definition',
                '(!) DROP PRIMARY KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertNull($this->blueprint->getTableNewPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                'different primary key definition',
                '(!) DROP PRIMARY KEY is not supported by SQLite: Migration must be created manually'
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertNull($this->blueprint->getAddedPrimaryKey());
    }
}
