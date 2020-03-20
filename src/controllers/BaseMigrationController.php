<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use bizley\migration\Arranger;
use bizley\migration\ArrangerInterface;
use bizley\migration\Comparator;
use bizley\migration\ComparatorInterface;
use bizley\migration\Extractor;
use bizley\migration\ExtractorInterface;
use bizley\migration\Generator;
use bizley\migration\GeneratorInterface;
use bizley\migration\HistoryManager;
use bizley\migration\HistoryManagerInterface;
use bizley\migration\Inspector;
use bizley\migration\InspectorInterface;
use bizley\migration\renderers\BlueprintRenderer;
use bizley\migration\renderers\BlueprintRendererInterface;
use bizley\migration\renderers\ColumnRenderer;
use bizley\migration\renderers\ForeignKeyRenderer;
use bizley\migration\renderers\IndexRenderer;
use bizley\migration\renderers\PrimaryKeyRenderer;
use bizley\migration\renderers\StructureRenderer;
use bizley\migration\renderers\StructureRendererInterface;
use bizley\migration\table\StructureBuilder;
use bizley\migration\table\StructureBuilderInterface;
use bizley\migration\TableMapper;
use bizley\migration\TableMapperInterface;
use bizley\migration\Updater;
use bizley\migration\UpdaterInterface;
use Closure;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Connection;

class BaseMigrationController extends Controller
{
    /** @var string Default command action. */
    public $defaultAction = 'list';

    /**
     * @var Connection|array|string DB connection object, configuration array, or the application component ID of
     * the DB connection.
     */
    public $db = 'db';

    /**
     * @var string Name of the table for keeping applied migration information.
     * The same as in yii\console\controllers\MigrateController::$migrationTable.
     */
    public $migrationTable = '{{%migration}}';

    /** @var bool Whether to use general column schema instead of database specific. */
    public $generalSchema = true;

    /** @var string|array|Closure */
    public $historyManagerClass = HistoryManager::class;

    /** @var string|array|Closure */
    public $tableMapperClass = TableMapper::class;

    /** @var string|array|Closure */
    public $arrangerClass = Arranger::class;

    /** @var string|array|Closure */
    public $generatorClass = Generator::class;

    /** @var string|array|Closure */
    public $structureRendererClass = StructureRenderer::class;

    /** @var string|array|Closure */
    public $columnRendererClass = ColumnRenderer::class;

    /** @var string|array|Closure */
    public $primaryKeyRendererClass = PrimaryKeyRenderer::class;

    /** @var string|array|Closure */
    public $indexRendererClass = IndexRenderer::class;

    /** @var string|array|Closure */
    public $foreignKeyRendererClass = ForeignKeyRenderer::class;

    /** @var string|array|Closure */
    public $updaterClass = Updater::class;

    /** @var string|array|Closure */
    public $inspectorClass = Inspector::class;

    /** @var string|array|Closure */
    private $blueprintRendererClass = BlueprintRenderer::class;

    /** @var string|array|Closure */
    private $extractorClass = Extractor::class;

    /** @var string|array|Closure */
    private $structureBuilderClass = StructureBuilder::class;

    /** @var string|array|Closure */
    private $comparatorClass = Comparator::class;

    /** @var HistoryManagerInterface */
    private $historyManager;

    /**
     * @return HistoryManagerInterface
     * @throws InvalidConfigException
     */
    public function getHistoryManager(): HistoryManagerInterface
    {
        if ($this->historyManager === null) {
            $this->historyManager = Yii::createObject($this->historyManagerClass, [$this->db, $this->migrationTable]);
        }

        return $this->historyManager;
    }

    /** @var TableMapperInterface */
    private $tableMapper;

    /**
     * @return TableMapperInterface
     * @throws InvalidConfigException
     */
    public function getTableMapper(): TableMapperInterface
    {
        if ($this->tableMapper === null) {
            $this->tableMapper = Yii::createObject($this->tableMapperClass, [$this->db]);
        }

        return $this->tableMapper;
    }

    /** @var ArrangerInterface */
    private $arranger;

    /**
     * @return ArrangerInterface
     * @throws InvalidConfigException
     */
    public function getArranger(): ArrangerInterface
    {
        if ($this->arranger === null) {
            $this->arranger = Yii::createObject($this->arrangerClass, [$this->getTableMapper()]);
        }

        return $this->arranger;
    }

    /** @var StructureRendererInterface */
    private $structureRenderer;

    /**
     * @return StructureRendererInterface
     * @throws InvalidConfigException
     */
    public function getStructureRenderer(): StructureRendererInterface
    {
        if ($this->structureRenderer === null) {
            $this->structureRenderer = Yii::createObject(
                $this->structureRendererClass,
                [
                    Yii::createObject($this->columnRendererClass, [$this->generalSchema]),
                    Yii::createObject($this->primaryKeyRendererClass),
                    Yii::createObject($this->indexRendererClass),
                    Yii::createObject($this->foreignKeyRendererClass)
                ]
            );
        }

        return $this->structureRenderer;
    }

    /** @var GeneratorInterface */
    private $generator;

    /**
     * @return GeneratorInterface
     * @throws InvalidConfigException
     */
    public function getGenerator(): GeneratorInterface
    {
        if ($this->generator === null) {
            $this->generator = Yii::createObject(
                $this->generatorClass,
                [
                    $this->getTableMapper(),
                    $this->getStructureRenderer(),
                    $this->view
                ]
            );
        }

        return $this->generator;
    }

    /** @var ExtractorInterface */
    private $extractor;

    /**
     * @return ExtractorInterface
     * @throws InvalidConfigException
     */
    public function getExtractor(): ExtractorInterface
    {
        if ($this->extractor === null) {
            $this->extractor = Yii::createObject($this->extractorClass, [$this->db]);
        }

        return $this->extractor;
    }

    /** @var StructureBuilderInterface */
    private $structureBuilder;

    /**
     * @return StructureBuilderInterface
     * @throws InvalidConfigException
     */
    public function getStructureBuilder(): StructureBuilderInterface
    {
        if ($this->structureBuilder === null) {
            $this->structureBuilder = Yii::createObject($this->structureBuilderClass);
        }

        return $this->structureBuilder;
    }

    /** @var ComparatorInterface */
    private $comparator;

    /**
     * @return ComparatorInterface
     * @throws InvalidConfigException
     */
    public function getComparator(): ComparatorInterface
    {
        if ($this->comparator === null) {
            $this->comparator = Yii::createObject($this->comparatorClass, [$this->generalSchema]);
        }

        return $this->comparator;
    }

    /** @var InspectorInterface */
    private $inspector;

    /**
     * @return InspectorInterface
     * @throws InvalidConfigException
     */
    public function getInspector(): InspectorInterface
    {
        if ($this->inspector === null) {
            $this->inspector = Yii::createObject(
                $this->inspectorClass,
                [
                    $this->getHistoryManager(),
                    $this->getExtractor(),
                    $this->getStructureBuilder(),
                    $this->getComparator()
                ]
            );
        }

        return $this->inspector;
    }

    /** @var BlueprintRendererInterface */
    private $blueprintRenderer;

    /**
     * @return BlueprintRendererInterface
     * @throws InvalidConfigException
     */
    public function getBlueprintRenderer(): BlueprintRendererInterface
    {
        if ($this->blueprintRenderer === null) {
            $this->blueprintRenderer = Yii::createObject(
                $this->blueprintRendererClass,
                [

                ]
            );
        }

        return $this->blueprintRenderer;
    }

    /** @var UpdaterInterface */
    private $updater;

    /**
     * @return UpdaterInterface
     * @throws InvalidConfigException
     */
    public function getUpdater(): UpdaterInterface
    {
        if ($this->updater === null) {
            $this->updater = Yii::createObject(
                $this->updaterClass,
                [
                    $this->getTableMapper(),
                    $this->getInspector(),
                    $this->getBlueprintRenderer(),
                    $this->view
                ]
            );
        }

        return $this->updater;
    }
}
