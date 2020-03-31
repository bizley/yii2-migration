<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Generator;
use bizley\migration\renderers\StructureRendererInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;
use bizley\migration\TableMapperInterface;
use bizley\migration\TableMissingException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\View;
use yii\db\TableSchema;

use function is_string;

class GeneratorTest extends TestCase
{
    /** @var TableMapperInterface|MockObject */
    private $mapper;

    /** @var MockObject|View */
    private $view;

    /** @var StructureRendererInterface|MockObject */
    private $renderer;

    /** @var Generator */
    private $generator;

    protected function setUp(): void
    {
        $this->mapper = $this->createMock(TableMapperInterface::class);
        $this->view = $this->createMock(View::class);
        $this->renderer = $this->createMock(StructureRendererInterface::class);
        $this->generator = new Generator(
            $this->mapper,
            $this->renderer,
            $this->view
        );
    }

    /** @test */
    public function shouldProperlyReturnCreateTableMigrationTemplate(): void
    {
        $this->assertSame(
            Yii::getAlias('@bizley/migration/views/migration.php'),
            $this->generator->getCreateTableMigrationTemplate()
        );
    }

    /** @test */
    public function shouldProperlyReturnCreateForeignKeysMigrationTemplate(): void
    {
        $this->assertSame(
            Yii::getAlias('@bizley/migration/views/migration.php'),
            $this->generator->getCreateForeignKeysMigrationTemplate()
        );
    }

    public function providerForNamespaces(): array
    {
        return [
            [null, null],
            ['', null],
            ['a/b/c', 'a\\b\\c'],
            ['a\\b\\c', 'a\\b\\c'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForNamespaces
     * @param string|null $namespace
     * @param string|null $expected
     * @throws TableMissingException
     */
    public function shouldProperlyNormalizeNamespace(?string $namespace, ?string $expected): void
    {
        $this->mapper->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $this->view->expects($this->once())->method('renderFile')->with(
            $this->callback(
                static function (string $template) {
                    return is_string($template);
                }
            ),
            $this->callback(
                static function (array $params) use ($expected) {
                    return $params['namespace'] === $expected;
                }
            )
        )->willReturn('string');

        $this->generator->generateForTable('table', 'migration', [], true, '', $namespace);
    }

    /**
     * @test
     * @throws TableMissingException
     */
    public function shouldThrowExceptionWhenTableIsMissing(): void
    {
        $this->expectException(TableMissingException::class);
        $this->mapper->method('getTableSchema')->willReturn(null);
        $this->generator->generateForTable('table', 'migration');
    }

    /**
     * @test
     * @throws TableMissingException
     */
    public function shouldProperlyGenerateForTable(): void
    {
        $this->mapper->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $structure = $this->createMock(StructureInterface::class);
        $this->mapper->method('getStructureOf')->willReturn($structure);
        $this->mapper->method('getSchemaType')->willReturn('schema-type');
        $this->mapper->method('getEngineVersion')->willReturn('engine-version');

        $this->renderer->expects($this->once())->method('renderStructureUp')->with(
            $structure,
            8,
            'schema-type',
            'engine-version',
            false,
            'prefix'
        )->willReturn('bodyUp');

        $this->renderer->expects($this->once())->method('renderStructureDown')->with(
            $structure,
            8,
            false,
            'prefix'
        )->willReturn('bodyDown');

        $this->view->expects($this->once())->method('renderFile')->with(
            $this->callback(
                static function (string $template) {
                    return is_string($template);
                }
            ),
            $this->callback(
                static function (array $params) {
                    return $params['namespace'] === 'a\\b\\c'
                        && $params['className'] === 'migration'
                        && $params['bodyUp'] === 'bodyUp'
                        && $params['bodyDown'] === 'bodyDown';
                }
            )
        )->willReturn('string');

        $this->generator->generateForTable('table', 'migration', ['tab-ref'], false, 'prefix', 'a\\b\\c');
    }

    /** @test */
    public function shouldProperlyGenerateForForeignKeys(): void
    {
        $foreignKeyFirst = $this->createMock(ForeignKeyInterface::class);
        $foreignKeySecond = $this->createMock(ForeignKeyInterface::class);

        $this->renderer->expects($this->once())->method('renderForeignKeysUp')->with(
            [$foreignKeyFirst, $foreignKeySecond],
            8,
            false,
            'prefix'
        )->willReturn('bodyUp');

        $this->renderer->expects($this->once())->method('renderForeignKeysDown')->with(
            [$foreignKeySecond, $foreignKeyFirst],
            8,
            false,
            'prefix'
        )->willReturn('bodyDown');

        $this->view->expects($this->once())->method('renderFile')->with(
            $this->callback(
                static function (string $template) {
                    return is_string($template);
                }
            ),
            $this->callback(
                static function (array $params) {
                    return $params['namespace'] === 'a\\b\\c'
                        && $params['className'] === 'migration'
                        && $params['bodyUp'] === 'bodyUp'
                        && $params['bodyDown'] === 'bodyDown';
                }
            )
        )->willReturn('string');

        $this->generator->generateForForeignKeys(
            [$foreignKeyFirst, $foreignKeySecond],
            'migration',
            false,
            'prefix',
            'a\\b\\c'
        );
    }

    /** @test */
    public function shouldReturnSuppressedForeignKeys(): void
    {
        $this->mapper->method('getSuppressedForeignKeys')->willReturn([]);
        $this->assertSame([], $this->generator->getSuppressedForeignKeys());
    }
}
