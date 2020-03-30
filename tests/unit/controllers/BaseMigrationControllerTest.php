<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\migration\controllers\BaseMigrationController;
use bizley\tests\unit\stubs\GenericConstructorClass;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\View;
use yii\db\Connection;

class BaseMigrationControllerTest extends TestCase
{
    /** @var BaseMigrationController */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new BaseMigrationController('id', $this->createMock(Module::class));
        $this->controller->db = $this->createMock(Connection::class);
        $this->controller->view = $this->createMock(View::class);
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongHistoryManager(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->historyManagerClass = GenericConstructorClass::class;
        $this->controller->getHistoryManager();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongTableMapper(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->tableMapperClass = GenericConstructorClass::class;
        $this->controller->getTableMapper();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongArranger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->arrangerClass = GenericConstructorClass::class;
        $this->controller->getArranger();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongStructureRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureRendererClass = GenericConstructorClass::class;
        $this->controller->getStructureRenderer();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongGenerator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->generatorClass = GenericConstructorClass::class;
        $this->controller->getGenerator();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongExtractor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->extractorClass = GenericConstructorClass::class;
        $this->controller->getExtractor();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongStructureBuilder(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->structureBuilderClass = GenericConstructorClass::class;
        $this->controller->getStructureBuilder();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongComparator(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->comparatorClass = GenericConstructorClass::class;
        $this->controller->getComparator();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongInspector(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->inspectorClass = GenericConstructorClass::class;
        $this->controller->getInspector();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongBlueprintRenderer(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->blueprintRendererClass = GenericConstructorClass::class;
        $this->controller->getBlueprintRenderer();
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenWrongUpdater(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->controller->updaterClass = GenericConstructorClass::class;
        $this->controller->getUpdater();
    }
}
