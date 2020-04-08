<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Extractor;
use bizley\migration\table\StructureChangeInterface;
use bizley\tests\stubs\GoodMigration;
use bizley\tests\stubs\WrongMigration;
use ErrorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Connection;

final class ExtractorTest extends TestCase
{
    /** @var MockObject|Connection */
    private $db;

    /** @var Extractor */
    private $extractor;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->extractor = new Extractor($this->db);
    }

    /** @test */
    public function shouldReturnNullWhenThereAreNoChanges(): void
    {
        $this->assertNull($this->extractor->getChanges());
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldThrowExceptionWhenMigrationIsNotNamespacedAndThereIsNoFile(): void
    {
        $this->expectException(ErrorException::class);

        $this->extractor->extract('non-existing', []);
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldThrowExceptionWhenSubjectIsNotMigrationChangesInterface(): void
    {
        $this->expectException(ErrorException::class);

        $this->extractor->extract(WrongMigration::class, []);
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldReturnChangesWhenSubjectIsNamespaced(): void
    {
        $this->extractor->extract(GoodMigration::class, []);
        $changes = $this->extractor->getChanges();
        $this->assertSame(['table'], array_keys($changes));
        $this->assertInstanceOf(StructureChangeInterface::class, array_values($changes)[0][0]);

        $this->assertSame(Yii::getAlias('@bizley/migration/dummy/Migration.php'), Yii::$classMap['yii\db\Migration']);
    }

    /**
     * @test
     * @throws ErrorException
     */
    public function shouldReturnChangesWhenSubjectIsNotNamespaced(): void
    {
        $this->extractor->extract('good_migration', ['tests/stubs']);
        $changes = $this->extractor->getChanges();
        $this->assertSame(['table'], array_keys($changes));
        $this->assertInstanceOf(StructureChangeInterface::class, array_values($changes)[0][0]);

        $this->assertSame(Yii::getAlias('@bizley/migration/dummy/Migration.php'), Yii::$classMap['yii\db\Migration']);
    }
}
