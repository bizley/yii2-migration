<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\InspectorInterface;
use bizley\migration\renderers\BlueprintRendererInterface;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;
use bizley\migration\TableMapperInterface;
use bizley\migration\TableMissingException;
use bizley\migration\Updater;
use ErrorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\db\TableSchema;

use function is_string;

final class UpdaterTest extends TestCase
{
    /** @var TableMapperInterface|MockObject */
    private $mapper;

    /** @var MockObject|View */
    private $view;

    /** @var BlueprintRendererInterface|MockObject */
    private $renderer;

    /** @var Updater */
    private $updater;

    /** @var InspectorInterface|MockObject */
    private $inspector;

    protected function setUp(): void
    {
        $this->mapper = $this->createMock(TableMapperInterface::class);
        $this->view = $this->createMock(View::class);
        $this->renderer = $this->createMock(BlueprintRendererInterface::class);
        $this->inspector = $this->createMock(InspectorInterface::class);
        $this->updater = new Updater(
            $this->mapper,
            $this->inspector,
            $this->renderer,
            $this->view
        );
    }

    /** @test */
    public function shouldProperlyReturnUpdateTableMigrationTemplate(): void
    {
        $this->assertSame(
            Yii::getAlias('@bizley/migration/views/migration.php'),
            $this->updater->getUpdateTableMigrationTemplate()
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
     * @throws NotSupportedException
     */
    public function shouldProperlyNormalizeNamespace(?string $namespace, ?string $expected): void
    {
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

        $this->updater->generateFromBlueprint(
            $this->createMock(BlueprintInterface::class),
            'migration',
            true,
            '',
            $namespace
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     * @throws TableMissingException
     * @throws ErrorException
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenTableIsMissing(): void
    {
        $this->expectException(TableMissingException::class);
        $this->mapper->method('getTableSchema')->willReturn(null);
        $this->updater->prepareBlueprint('table', true, [], []);
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws TableMissingException
     */
    public function shouldProperlyPrepareBlueprint(): void
    {
        $this->mapper->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $structure = $this->createMock(StructureInterface::class);
        $this->mapper->method('getStructureOf')->willReturn($structure);
        $this->mapper->method('getSchemaType')->willReturn('schema-type');
        $this->mapper->method('getEngineVersion')->willReturn('engine-version');

        $this->inspector->expects($this->once())->method('prepareBlueprint')->with(
            $structure,
            false,
            ['a'],
            ['b'],
            'schema-type',
            'engine-version'
        )->willReturn($this->createMock(BlueprintInterface::class));

        $this->updater->prepareBlueprint('table', false, ['a'], ['b']);
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldProperlyGenerateFromBlueprint(): void
    {
        $blueprint = $this->createMock(BlueprintInterface::class);
        $this->mapper->method('getSchemaType')->willReturn('schema-type');
        $this->mapper->method('getEngineVersion')->willReturn('engine-version');

        $this->renderer->expects($this->once())->method('renderUp')->with(
            $blueprint,
            8,
            'schema-type',
            'engine-version',
            false,
            'prefix'
        )->willReturn('bodyUp');

        $this->renderer->expects($this->once())->method('renderDown')->with(
            $blueprint,
            8,
            'schema-type',
            'engine-version',
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

        $this->updater->generateFromBlueprint($blueprint, 'migration', false, 'prefix', 'a\\b\\c');
    }
}
