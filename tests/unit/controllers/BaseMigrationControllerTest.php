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
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\View;
use yii\console\Request;
use yii\console\Response;
use yii\db\Connection;

/** @group controller */
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

    /** @test */
    public function shouldThrowExceptionWhenWrongHistoryManager(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->historyManagerClass = GenericConstructorClass::class;
        $this->controller->getHistoryManager();
    }

    /** @test */
    public function shouldConfigureHistoryManager(): void
    {
         self::assertInstanceOf(HistoryManager::class, $this->controller->getHistoryManager());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongTableMapper(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->tableMapperClass = GenericConstructorClass::class;
        $this->controller->getTableMapper();
    }

    /** @test */
    public function shouldConfigureTableMapper(): void
    {
        self::assertInstanceOf(TableMapper::class, $this->controller->getTableMapper());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongArranger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->arrangerClass = GenericConstructorClass::class;
        $this->controller->getArranger();
    }

    /** @test */
    public function shouldConfigureArranger(): void
    {
        self::assertInstanceOf(Arranger::class, $this->controller->getArranger());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongStructureRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureRendererClass = GenericConstructorClass::class;
        $this->controller->getStructureRenderer();
    }

    /** @test */
    public function shouldConfigureStructureRenderer(): void
    {
        self::assertInstanceOf(StructureRenderer::class, $this->controller->getStructureRenderer());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongGenerator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->generatorClass = GenericConstructorClass::class;
        $this->controller->getGenerator();
    }

    /** @test */
    public function shouldConfigureGenerator(): void
    {
        self::assertInstanceOf(Generator::class, $this->controller->getGenerator());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongExtractor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->extractorClass = GenericConstructorClass::class;
        $this->controller->getExtractor();
    }

    /** @test */
    public function shouldConfigureExtractor(): void
    {
        self::assertInstanceOf(Extractor::class, $this->controller->getExtractor());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongSqlExtractor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->extractorClass = GenericConstructorClass::class;
        $this->controller->getSqlExtractor();
    }

    /** @test */
    public function shouldConfigureSqlExtractor(): void
    {
        self::assertInstanceOf(Extractor::class, $this->controller->getSqlExtractor());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongStructureBuilder(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureBuilderClass = GenericConstructorClass::class;
        $this->controller->getStructureBuilder();
    }

    /** @test */
    public function shouldConfigureStructureBuilder(): void
    {
        self::assertInstanceOf(StructureBuilder::class, $this->controller->getStructureBuilder());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongComparator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->comparatorClass = GenericConstructorClass::class;
        $this->controller->getComparator();
    }

    /** @test */
    public function shouldConfigureComparator(): void
    {
        self::assertInstanceOf(Comparator::class, $this->controller->getComparator());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongInspector(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->inspectorClass = GenericConstructorClass::class;
        $this->controller->getInspector();
    }

    /** @test */
    public function shouldConfigureInspector(): void
    {
        self::assertInstanceOf(Inspector::class, $this->controller->getInspector());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongBlueprintRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->blueprintRendererClass = GenericConstructorClass::class;
        $this->controller->getBlueprintRenderer();
    }

    /** @test */
    public function shouldConfigureBlueprintRenderer(): void
    {
        self::assertInstanceOf(BlueprintRenderer::class, $this->controller->getBlueprintRenderer());
    }

    /** @test */
    public function shouldThrowExceptionWhenWrongUpdater(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->updaterClass = GenericConstructorClass::class;
        $this->controller->getUpdater();
    }

    /** @test */
    public function shouldConfigureUpdater(): void
    {
        self::assertInstanceOf(Updater::class, $this->controller->getUpdater());
    }
}
