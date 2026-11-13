<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\migration\Arranger;
use bizley\migration\Comparator;
use bizley\migration\controllers\BaseMigrationController;
use bizley\migration\Extractor;
use bizley\migration\Generator;
use bizley\migration\HistoryManager;
use bizley\migration\Inspector;
use bizley\migration\renderers\BlueprintRenderer;
use bizley\migration\renderers\StructureRenderer;
use bizley\migration\table\StructureBuilder;
use bizley\migration\TableMapper;
use bizley\migration\Updater;
use bizley\tests\stubs\GenericConstructorClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\View;
use yii\console\Request;
use yii\console\Response;
use yii\db\Connection;

#[Group('controller')]
final class BaseMigrationControllerTest extends TestCase
{
    /** @var BaseMigrationController */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new BaseMigrationController(
            'id',
            $this->createMock(Module::class),
            [
                'request' => Request::class,
                'response' => Response::class
            ]
        );
        $this->controller->db = $this->createMock(Connection::class);
        $this->controller->view = $this->createMock(View::class);
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongHistoryManager(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->historyManagerClass = GenericConstructorClass::class;
        $this->controller->getHistoryManager();
    }

    #[Test]
    public function shouldConfigureHistoryManager(): void
    {
         self::assertInstanceOf(HistoryManager::class, $this->controller->getHistoryManager());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongTableMapper(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->tableMapperClass = GenericConstructorClass::class;
        $this->controller->getTableMapper();
    }

    #[Test]
    public function shouldConfigureTableMapper(): void
    {
        self::assertInstanceOf(TableMapper::class, $this->controller->getTableMapper());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongArranger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->arrangerClass = GenericConstructorClass::class;
        $this->controller->getArranger();
    }

    #[Test]
    public function shouldConfigureArranger(): void
    {
        self::assertInstanceOf(Arranger::class, $this->controller->getArranger());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongStructureRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureRendererClass = GenericConstructorClass::class;
        $this->controller->getStructureRenderer();
    }

    #[Test]
    public function shouldConfigureStructureRenderer(): void
    {
        self::assertInstanceOf(StructureRenderer::class, $this->controller->getStructureRenderer());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongGenerator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->generatorClass = GenericConstructorClass::class;
        $this->controller->getGenerator();
    }

    #[Test]
    public function shouldConfigureGenerator(): void
    {
        self::assertInstanceOf(Generator::class, $this->controller->getGenerator());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongExtractor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->extractorClass = GenericConstructorClass::class;
        $this->controller->getExtractor();
    }

    #[Test]
    public function shouldConfigureExtractor(): void
    {
        self::assertInstanceOf(Extractor::class, $this->controller->getExtractor());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongSqlExtractor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->extractorClass = GenericConstructorClass::class;
        $this->controller->getSqlExtractor();
    }

    #[Test]
    public function shouldConfigureSqlExtractor(): void
    {
        self::assertInstanceOf(Extractor::class, $this->controller->getSqlExtractor());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongStructureBuilder(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureBuilderClass = GenericConstructorClass::class;
        $this->controller->getStructureBuilder();
    }

    #[Test]
    public function shouldConfigureStructureBuilder(): void
    {
        self::assertInstanceOf(StructureBuilder::class, $this->controller->getStructureBuilder());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongComparator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->comparatorClass = GenericConstructorClass::class;
        $this->controller->getComparator();
    }

    #[Test]
    public function shouldConfigureComparator(): void
    {
        self::assertInstanceOf(Comparator::class, $this->controller->getComparator());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongInspector(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->inspectorClass = GenericConstructorClass::class;
        $this->controller->getInspector();
    }

    #[Test]
    public function shouldConfigureInspector(): void
    {
        self::assertInstanceOf(Inspector::class, $this->controller->getInspector());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongBlueprintRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->blueprintRendererClass = GenericConstructorClass::class;
        $this->controller->getBlueprintRenderer();
    }

    #[Test]
    public function shouldConfigureBlueprintRenderer(): void
    {
        self::assertInstanceOf(BlueprintRenderer::class, $this->controller->getBlueprintRenderer());
    }

    #[Test]
    public function shouldThrowExceptionWhenWrongUpdater(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->updaterClass = GenericConstructorClass::class;
        $this->controller->getUpdater();
    }

    #[Test]
    public function shouldConfigureUpdater(): void
    {
        self::assertInstanceOf(Updater::class, $this->controller->getUpdater());
    }
}
