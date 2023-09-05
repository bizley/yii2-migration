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

/** @group comparator */
class ComparatorNonSqliteTest extends TestCase
{
    /** @var StructureInterface&MockObject */
    public $newStructure;

    /** @var StructureInterface&MockObject */
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
        $column->setLength(5);
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

        self::assertTrue($column1->isFirst());
        self::assertNull($column1->getAfter());
        self::assertFalse($column2->isFirst());
        self::assertSame('col1', $column2->getAfter());
        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "missing column 'col1'",
                "missing column 'col2'",
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col1', 'col2'], \array_keys($this->blueprint->getAddedColumns()));
    }

    /**
     * @test
     */
    public function shouldDropColumn(): void
    {
        $column1 = $this->getColumn('col1');
        $this->newStructure->method('getColumns')->willReturn([]);
        $this->oldStructure->method('getColumns')->willReturn(['col1' => $column1]);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(["excessive column 'col1'"], $this->blueprint->getDescriptions());
        self::assertSame(['col1'], \array_keys($this->blueprint->getDroppedColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: type (DB: \"a\" != MIG: \"b\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: not null (DB: TRUE != MIG: FALSE)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: length (DB: \"10\" != MIG: \"2\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: length (DB: \"10, 2\" != MIG: \"9, 3\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     */
    public function shouldNotAlterColumnForDecimalLengthWithoutScaleVariant1(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setLength('10, 0');
        $columnOld = $this->getColumn('col');
        $columnOld->setLength('10');
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
    public function shouldNotAlterColumnForDecimalLengthWithoutScaleVariant2(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setLength('9');
        $columnOld = $this->getColumn('col');
        $columnOld->setLength('9, 0');
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
            ["different 'col' column property: unique (DB: FALSE != MIG: TRUE)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     */
    public function shouldNotAlterColumnForIsUnique(): void
    {
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
        $index->setColumns(['col']);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->oldStructure->method('getIndex')->willReturn($index);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->newStructure->method('getIndex')->willReturn($index);

        $this->compare();

        self::assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
     */
    public function shouldAlterColumnForIsUniqueWhenIndexIsMulticolumn(): void
    {
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

        self::assertTrue($this->blueprint->isPending());
    }

    /**
     * @test
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
            ["different 'col' column property: unsigned (DB: FALSE != MIG: TRUE)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: default (DB: [\"a\"] != MIG: [\"b\"])"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["different 'col' column property: default (DB: \"NULL\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: comment (DB: \"abc\" != MIG: \"def\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: append (DB: \"abc\" != MIG: \"def\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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

        self::assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
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
            ["different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
     */
    public function shouldNotAlterColumnForGetAppendWithAutoincrementAndOldAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAutoIncrement(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('AUTOINCREMENT');
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
    public function shouldNotAlterColumnForGetAppendWithAutoincrementAndNewAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTOINCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(true);
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
    public function shouldNotAlterColumnForGetAppendWithAutoincrementAndOldAppendVariant2(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAutoIncrement(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('AUTO_INCREMENT');
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
    public function shouldNotAlterColumnForGetAppendWithAutoincrementAndNewAppendVariant2(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('AUTO_INCREMENT');
        $columnOld = $this->getColumn('col');
        $columnOld->setAutoIncrement(true);
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

        self::assertFalse($this->blueprint->isPending());
    }

    /**
     * @test
     */
    public function shouldNotAlterColumnForGetAppendWithPKAndEmptyNewAppend(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setPrimaryKey(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('PRIMARY KEY');
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
    public function shouldNotAlterColumnForGetAppendWithPKAndEmptyOldAppendVariant2(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setAppend('IDENTITY PRIMARY KEY');
        $columnOld = $this->getColumn('col');
        $columnOld->setPrimaryKey(true);
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
    public function shouldNotAlterColumnForGetAppendWithPKAndEmptyNewAppendVariant2(): void
    {
        $columnNew = $this->getColumn('col');
        $columnNew->setPrimaryKey(true);
        $columnOld = $this->getColumn('col');
        $columnOld->setAppend('IDENTITY PRIMARY KEY');
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["different 'col' column property: append (DB: \"PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: append (DB: \"AUTO_INCREMENT\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: append (DB: \"AUTOINCREMENT\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
            ["different 'col' column property: append (DB: \"IDENTITY PRIMARY KEY\" != MIG: NULL)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['col'], \array_keys($this->blueprint->getAlteredColumns()));
        self::assertSame(['col'], \array_keys($this->blueprint->getUnalteredColumns()));
    }

    /**
     * @test
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
                "missing foreign key 'fk2'",
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk', 'fk2'], \array_keys($this->blueprint->getAddedForeignKeys()));
    }

    /**
     * @test
     */
    public function shouldDropForeignKey(): void
    {
        $foreignKey = $this->getForeignKey('fk');
        $this->newStructure->method('getForeignKeys')->willReturn([]);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["excessive foreign key 'fk'"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
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
                "different foreign key 'fk2' columns (DB: [\"a\",\"b\"] != MIG: [\"d\",\"e\"])",
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk', 'fk2'], \array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk', 'fk2'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
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
            ["different foreign key 'fk' referred columns (DB: [\"a\",\"b\"] != MIG: [\"c\",\"b\"])"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], \array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
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
            ["different foreign key 'fk' referred table (DB: \"a\" != MIG: \"b\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], \array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
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
            ["different foreign key 'fk' ON UPDATE constraint (DB: \"CASCADE\" != MIG: \"RESTRICT\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], \array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
     */
    public function shouldReplaceForeignKeyWithDifferentOnDeleteConstraint(): void
    {
        $foreignKeyNew = $this->getForeignKey('fk');
        $foreignKeyNew->setOnDelete('RESTRICT');
        $foreignKeyOld = $this->getForeignKey('fk');
        $foreignKeyOld->setOnDelete('CASCADE');
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyNew]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKeyNew);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKeyOld]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKeyOld);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["different foreign key 'fk' ON DELETE constraint (DB: \"RESTRICT\" != MIG: \"CASCADE\")"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['fk'], \array_keys($this->blueprint->getAddedForeignKeys()));
        self::assertSame(['fk'], \array_keys($this->blueprint->getDroppedForeignKeys()));
    }

    /**
     * @test
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
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
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
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        self::assertNull($this->blueprint->getTableNewPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
    }

    /**
     * @test
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
            ['different primary key definition'],
            $this->blueprint->getDescriptions()
        );
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        self::assertSame($primaryKeyOld, $this->blueprint->getDroppedPrimaryKey());
    }

    /**
     * @test
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

    /**
     * @test
     */
    public function shouldAddPrimaryKeyWhenPKInfoNotAddedAlready(): void
    {
        $column = $this->getColumn('col');
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
        self::assertNotNull($this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertNull($this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
     */
    public function shouldAddPrimaryKeyWhenPKInfoNotAlteredAlready(): void
    {
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertNotNull($this->blueprint->getAddedPrimaryKey());
    }

    /**
     * @test
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        self::assertSame('abc', $column->getAppend());
    }

    /**
     * @test
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame($primaryKeyNew, $this->blueprint->getTableNewPrimaryKey());
        self::assertNull($this->blueprint->getTableOldPrimaryKey());
        self::assertSame($primaryKeyNew, $this->blueprint->getAddedPrimaryKey());
        self::assertSame('abc', $columnNew->getAppend());
    }

    /**
     * @test
     */
    public function shouldAddIndex(): void
    {
        $index = $this->getIndex('idx');
        $index->setColumns(['a']);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $this->oldStructure->method('getIndexes')->willReturn([]);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["missing index 'idx'"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['idx'], \array_keys($this->blueprint->getAddedIndexes()));
    }

    /**
     * @test
     */
    public function shouldNotAddIndexWhenItComesFromForeignKey(): void
    {
        $index = $this->getIndex('idx');
        $index->setColumns(['a']);
        $this->newStructure->method('getIndexes')->willReturn(['idx' => $index]);
        $foreignKey = $this->getForeignKey('fk');
        $foreignKey->setColumns(['a']);
        $this->newStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);
        $this->newStructure->method('getForeignKey')->willReturn($foreignKey);
        $this->oldStructure->method('getIndexes')->willReturn([]);
        $this->oldStructure->method('getForeignKeys')->willReturn(['fk' => $foreignKey]);
        $this->oldStructure->method('getForeignKey')->willReturn($foreignKey);

        $this->compare();

        self::assertFalse($this->blueprint->isPending());
        self::assertSame([], $this->blueprint->getDescriptions());
    }

    /**
     * @test
     */
    public function shouldDropIndex(): void
    {
        $index = $this->getIndex('idx');
        $index->setColumns(['a']);
        $this->newStructure->method('getIndexes')->willReturn([]);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $index]);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["excessive index 'idx'"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['idx'], \array_keys($this->blueprint->getDroppedIndexes()));
    }

    /**
     * @test
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["different index 'idx' definition (DB: unique FALSE != MIG: unique TRUE)"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['idx'], \array_keys($this->blueprint->getAddedIndexes()));
        self::assertSame(['idx'], \array_keys($this->blueprint->getDroppedIndexes()));
    }

    /**
     * @test
     */
    public function shouldReplaceMoreThanOneIndex(): void
    {
        $indexNew = $this->getIndex('idx');
        $indexNew->setColumns(['a']);
        $indexNew->setUnique(false);
        $indexNew2 = $this->getIndex('idx2');
        $indexNew2->setColumns(['b']);

        $indexOld = $this->getIndex('idx');
        $indexOld->setColumns(['a']);
        $indexOld->setUnique(true);
        $indexOld2 = $this->getIndex('idx2');
        $indexOld2->setColumns(['c']);

        $this->newStructure->method('getIndexes')->willReturn(['idx' => $indexNew, 'idx2' => $indexNew2]);
        $this->newStructure->method('getIndex')->willReturnOnConsecutiveCalls($indexNew, $indexNew2);
        $this->oldStructure->method('getIndexes')->willReturn(['idx' => $indexOld, 'idx2' => $indexOld2]);
        $this->oldStructure->method('getIndex')->willReturnOnConsecutiveCalls($indexOld, $indexOld2);

        $this->compare();

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            [
                "different index 'idx' definition (DB: unique FALSE != MIG: unique TRUE)",
                "different index 'idx2' columns (DB: [\"b\"]) != MIG: ([\"c\"]))",
            ],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['idx', 'idx2'], \array_keys($this->blueprint->getAddedIndexes()));
        self::assertSame(['idx', 'idx2'], \array_keys($this->blueprint->getDroppedIndexes()));
    }

    /**
     * @test
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

        self::assertTrue($this->blueprint->isPending());
        self::assertSame(
            ["different index 'idx' columns (DB: [\"a\"]) != MIG: ([\"b\"]))"],
            $this->blueprint->getDescriptions()
        );
        self::assertSame(['idx'], \array_keys($this->blueprint->getAddedIndexes()));
        self::assertSame(['idx'], \array_keys($this->blueprint->getDroppedIndexes()));
    }
}
