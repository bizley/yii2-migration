<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\tests\unit\stubs\MigrationControllerStub;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\View;
use yii\db\Connection;

class MigrationControllerTest extends TestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new MigrationControllerStub('id', $this->createMock(Module::class));
        $this->controller->db = $this->createMock(Connection::class);
        $this->controller->view = $this->createMock(View::class);
        Yii::setAlias('@bizley/tests', 'tests');
    }

    public function providerForOptions(): array
    {
        return [
            'default' => ['default', ['color', 'interactive', 'help', 'db']],
            'create' => [
                'create',
                [
                    'color',
                    'interactive',
                    'help',
                    'db',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables'
                ]
            ],
            'update' => [
                'update',
                [
                    'color',
                    'interactive',
                    'help',
                    'db',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables',
                    'onlyShow',
                    'skipMigrations'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForOptions
     * @param string $actionId
     * @param array $expected
     */
    public function shouldReturnProperOptions(string $actionId, array $expected): void
    {
        $this->assertSame($expected, $this->controller->options($actionId));
    }

    /** @test */
    public function shouldReturnProperOptionAliases(): void
    {
        $this->assertSame(
            [
                'h' => 'help',
                'fh' => 'fixHistory',
                'gs' => 'generalSchema',
                'mn' => 'migrationNamespace',
                'mp' => 'migrationPath',
                'mt' => 'migrationTable',
                'os' => 'onlyShow',
                'tp' => 'useTablePrefix',
            ],
            $this->controller->optionAliases()
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldReturnFalseWhenParentBeforeActionReturnsFalse(): void
    {
        $this->controller->on(
            \yii\base\Controller::EVENT_BEFORE_ACTION,
            static function ($event) {
                $event->isValid = false;
            }
        );
        $this->assertFalse($this->controller->beforeAction($this->createMock(Action::class)));
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldReturnTrueBeforeDefaultAction(): void
    {
        $this->assertTrue($this->controller->beforeAction($this->createMock(Action::class)));
        $this->assertStringContainsString(
            'Yii 2 Migration Generator Tool v',
            MigrationControllerStub::$stdout
        );
    }

    public function providerForActionIds(): array
    {
        return [
            ['create'],
            ['update'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenNeitherPathOrNamespaceGivenInBeforeNonDefaultAction(string $actionId): void
    {
        $this->expectException(InvalidConfigException::class);
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationNamespace = null;
        $this->controller->migrationPath = null;
        $this->controller->beforeAction($action);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSkippedMigrationsInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'migrations';
        $this->controller->skipMigrations = ['a\\b\\'];
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['a\\b'], $this->controller->skipMigrations);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSingleMigrationNamespaceInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationNamespace = 'bizley\\tests';
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['bizley\\tests'], $this->controller->migrationNamespace);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSingleMigrationPathInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'tests';
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['tests'], $this->controller->migrationPath);
    }
}
