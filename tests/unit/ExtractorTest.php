<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Extractor;
use bizley\migration\table\StructureChange;
use bizley\tests\stubs\GoodMigration;
use bizley\tests\stubs\WrongMigration;
use ErrorException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Connection;

#[Group('extractor')]
final class ExtractorTest extends TestCase
{
    /** @var Extractor */
    private $extractor;

    protected function setUp(): void
    {
        $this->extractor = new Extractor($this->createMock(Connection::class));
    }

    #[Test]
    public function shouldReturnNullWhenThereAreNoChanges(): void
    {
        self::assertNull($this->extractor->getChanges());
    }

    #[Test]
    public function shouldThrowExceptionWhenMigrationIsNotNamespacedAndThereIsNoFile(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("File 'non-existing.php' can not be found!");

        $this->extractor->extract('non-existing', []);
    }

    #[Test]
    public function shouldThrowExceptionWhenSubjectIsNotMigrationChangesInterface(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            "Class 'bizley\\tests\\stubs\\WrongMigration' must implement bizley\migration\dummy\MigrationChangesInterface."
        );

        $this->extractor->extract(WrongMigration::class, []);
    }

    #[Test]
    public function shouldThrowExceptionWhenSubjectIsNotMigrationSqlInterface(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(
            "Class 'bizley\\tests\\stubs\\WrongMigration' must implement bizley\migration\dummy\MigrationSqlInterface."
        );

        $this->extractor->getSql(WrongMigration::class, [], 'up');
    }

    /**
     * @throws ErrorException
     */
    #[Test]
    public function shouldReturnChangesWhenSubjectIsNamespaced(): void
    {
        $this->extractor->extract(GoodMigration::class, []);
        $changes = $this->extractor->getChanges();
        self::assertNotNull($changes);
        self::assertSame(['table'], \array_keys($changes));
        self::assertEquals([new StructureChange()], $changes['table']);

        self::assertSame(Yii::getAlias('@bizley/migration/dummy/MigrationChanges.php'), Yii::$classMap['yii\db\Migration']);
    }

    /**
     * @throws ErrorException
     */
    #[Test]
    public function shouldReturnChangesWhenSubjectIsNotNamespaced(): void
    {
        $this->extractor->extract('good_migration', ['tests/stubs']);
        $changes = $this->extractor->getChanges();
        self::assertNotNull($changes);
        self::assertSame(['table'], \array_keys($changes));
        self::assertEquals([new StructureChange()], $changes['table']);

        self::assertSame(Yii::getAlias('@bizley/migration/dummy/MigrationChanges.php'), Yii::$classMap['yii\db\Migration']);
    }
}
