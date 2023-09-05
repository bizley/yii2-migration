<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Extractor;
use bizley\migration\table\StructureChangeInterface;
use bizley\tests\stubs\GoodMigration;
use bizley\tests\stubs\WrongMigration;
use ErrorException;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Connection;

/** @group extractor */
final class ExtractorTest extends TestCase
{
    /** @var Extractor */
    private $extractor;

    protected function setUp(): void
    {
        $this->extractor = new Extractor($this->createMock(Connection::class));
    }

    /** @test */
    public function shouldReturnNullWhenThereAreNoChanges(): void
    {
        self::assertNull($this->extractor->getChanges());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenMigrationIsNotNamespacedAndThereIsNoFile(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("File 'non-existing.php' can not be found!");

        $this->extractor->extract('non-existing', []);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenSubjectIsNotMigrationChangesInterface(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            "Class 'bizley\\tests\\stubs\\WrongMigration' must implement bizley\migration\dummy\MigrationChangesInterface."
        );

        $this->extractor->extract(WrongMigration::class, []);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenSubjectIsNotMigrationSqlInterface(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            "Class 'bizley\\tests\\stubs\\WrongMigration' must implement bizley\migration\dummy\MigrationSqlInterface."
        );

        $this->extractor->getSql(WrongMigration::class, [], 'up');
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldReturnChangesWhenSubjectIsNamespaced(): void
    {
        $this->extractor->extract(GoodMigration::class, []);
        $changes = $this->extractor->getChanges();
        self::assertSame(['table'], \array_keys($changes));
        self::assertInstanceOf(StructureChangeInterface::class, \array_values($changes)[0][0]);

        self::assertSame(Yii::getAlias('@bizley/migration/dummy/MigrationChanges.php'), Yii::$classMap['yii\db\Migration']);
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldReturnChangesWhenSubjectIsNotNamespaced(): void
    {
        $this->extractor->extract('good_migration', ['tests/stubs']);
        $changes = $this->extractor->getChanges();
        self::assertSame(['table'], \array_keys($changes));
        self::assertInstanceOf(StructureChangeInterface::class, \array_values($changes)[0][0]);

        self::assertSame(Yii::getAlias('@bizley/migration/dummy/MigrationChanges.php'), Yii::$classMap['yii\db\Migration']);
    }
}
