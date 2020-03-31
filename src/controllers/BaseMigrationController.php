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
use yii\db\Query;

/**
 * This is the foundation of MigrationController. All services are registered here.
 * To replace a service with your own provide the proper configuration name for it. The configuration can be a class
 * name, array with configuration, or closure, but the resulting service must implement the chosen service
 * interface. For more information refer to Yii::createObject() method.
 * @see https://www.yiiframework.com/doc/api/2.0/yii-baseyii#createObject()-detail
 * Default implementations require some constructor arguments so you must still add __construct() method in your version
 * even when you are not using constructor.
 */
class BaseMigrationController extends Controller
{
    /** @var string Default command action. */
    public $defaultAction = 'list';

    /**
     * @var Connection|object|array<string, mixed>|string DB connection object, configuration array, or the application
     * component ID of the DB connection.
     */
    public $db = 'db';

    /**
     * @var string Name of the table for keeping applied migration information.
     * The same as in yii\console\controllers\MigrateController::$migrationTable.
     */
    public $migrationTable = '{{%migration}}';

    /**
     * @var bool Whether to use general column schema instead of database specific.
     * Remember that with different database types general column schemas may be generated with different length.
     * MySQL examples:
     * > Column `varchar(255)`:
     * generalSchema=false: `$this->string(255)`
     * generalSchema=true: `$this->string()`
     * > Column `int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY`:
     * generalSchema=false: `$this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY')`
     * generalSchema=true: `$this->primaryKey()`
     * When column size is different from DBMS' default it's kept:
     * > Column `varchar(45)`:
     * generalSchema=false: `$this->string(45)`
     * generalSchema=true: `$this->string(45)`
     */
    public $generalSchema = true;

    /** @var string|array<string, mixed>|Closure */
    public $historyManagerClass = HistoryManager::class;

    /** @var string|array<string, mixed>|Closure */
    public $tableMapperClass = TableMapper::class;

    /** @var string|array<string, mixed>|Closure */
    public $arrangerClass = Arranger::class;

    /** @var string|array<string, mixed>|Closure */
    public $generatorClass = Generator::class;

    /** @var string|array<string, mixed>|Closure */
    public $structureRendererClass = StructureRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $columnRendererClass = ColumnRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $primaryKeyRendererClass = PrimaryKeyRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $indexRendererClass = IndexRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $foreignKeyRendererClass = ForeignKeyRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $updaterClass = Updater::class;

    /** @var string|array<string, mixed>|Closure */
    public $inspectorClass = Inspector::class;

    /** @var string|array<string, mixed>|Closure */
    public $blueprintRendererClass = BlueprintRenderer::class;

    /** @var string|array<string, mixed>|Closure */
    public $extractorClass = Extractor::class;

    /** @var string|array<string, mixed>|Closure */
    public $structureBuilderClass = StructureBuilder::class;

    /** @var string|array<string, mixed>|Closure */
    public $comparatorClass = Comparator::class;

    /** @var HistoryManagerInterface */
    private $historyManager;

    /**
     * @return HistoryManagerInterface
     * @throws InvalidConfigException
     */
    public function getHistoryManager(): HistoryManagerInterface
    {
        if ($this->historyManager === null) {
            $configuredObject = Yii::createObject(
                $this->historyManagerClass,
                [
                    $this->db,
                    new Query(),
                    $this->migrationTable
                ]
            );
            if (!$configuredObject instanceof HistoryManagerInterface) {
                throw new InvalidConfigException(
                    'HistoryManager must implement bizley\migration\HistoryManagerInterface'
                );
            }
            $this->historyManager = $configuredObject;
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
            $configuredObject = Yii::createObject($this->tableMapperClass, [$this->db]);
            if (!$configuredObject instanceof TableMapperInterface) {
                throw new InvalidConfigException(
                    'TableMapper must implement bizley\migration\TableMapperInterface.'
                );
            }
            $this->tableMapper = $configuredObject;
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
            $configuredObject = Yii::createObject($this->arrangerClass, [$this->getTableMapper()]);
            if (!$configuredObject instanceof ArrangerInterface) {
                throw new InvalidConfigException('Arranger must implement bizley\migration\ArrangerInterface.');
            }
            $this->arranger = $configuredObject;
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
            $configuredObject = Yii::createObject(
                $this->structureRendererClass,
                [
                    Yii::createObject($this->columnRendererClass, [$this->generalSchema]),
                    Yii::createObject($this->primaryKeyRendererClass),
                    Yii::createObject($this->indexRendererClass),
                    Yii::createObject($this->foreignKeyRendererClass)
                ]
            );
            if (!$configuredObject instanceof StructureRendererInterface) {
                throw new InvalidConfigException(
                    'StructureRenderer must implement bizley\migration\renderers\StructureRendererInterface.'
                );
            }
            $this->structureRenderer = $configuredObject;
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
            $configuredObject = Yii::createObject(
                $this->generatorClass,
                [
                    $this->getTableMapper(),
                    $this->getStructureRenderer(),
                    $this->view
                ]
            );
            if (!$configuredObject instanceof GeneratorInterface) {
                throw new InvalidConfigException('Generator must implement bizley\migration\GeneratorInterface.');
            }
            $this->generator = $configuredObject;
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
            $configuredObject = Yii::createObject($this->extractorClass, [$this->db]);
            if (!$configuredObject instanceof ExtractorInterface) {
                throw new InvalidConfigException('Extractor must implement bizley\migration\ExtractorInterface.');
            }
            $this->extractor = $configuredObject;
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
            $configuredObject = Yii::createObject($this->structureBuilderClass);
            if (!$configuredObject instanceof StructureBuilderInterface) {
                throw new InvalidConfigException(
                    'StructureBuilder must implement bizley\migration\table\StructureBuilderInterface.'
                );
            }
            $this->structureBuilder = $configuredObject;
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
            $configuredObject = Yii::createObject($this->comparatorClass, [$this->generalSchema]);
            if (!$configuredObject instanceof ComparatorInterface) {
                throw new InvalidConfigException(
                    'Comparator must implement bizley\migration\ComparatorInterface.'
                );
            }
            $this->comparator = $configuredObject;
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
            $configuredObject = Yii::createObject(
                $this->inspectorClass,
                [
                    $this->getHistoryManager(),
                    $this->getExtractor(),
                    $this->getStructureBuilder(),
                    $this->getComparator()
                ]
            );
            if (!$configuredObject instanceof InspectorInterface) {
                throw new InvalidConfigException('Inspector must implement bizley\migration\InspectorInterface.');
            }
            $this->inspector = $configuredObject;
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
            $configuredObject = Yii::createObject(
                $this->blueprintRendererClass,
                [
                    Yii::createObject($this->columnRendererClass, [$this->generalSchema]),
                    Yii::createObject($this->primaryKeyRendererClass),
                    Yii::createObject($this->indexRendererClass),
                    Yii::createObject($this->foreignKeyRendererClass)
                ]
            );
            if (!$configuredObject instanceof BlueprintRendererInterface) {
                throw new InvalidConfigException(
                    'BlueprintRenderer must implement bizley\migration\renderers\BlueprintRendererInterface.'
                );
            }
            $this->blueprintRenderer = $configuredObject;
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
            $configuredObject = Yii::createObject(
                $this->updaterClass,
                [
                    $this->getTableMapper(),
                    $this->getInspector(),
                    $this->getBlueprintRenderer(),
                    $this->view
                ]
            );
            if (!$configuredObject instanceof UpdaterInterface) {
                throw new InvalidConfigException('Updater must implement bizley\migration\UpdaterInterface.');
            }
            $this->updater = $configuredObject;
        }

        return $this->updater;
    }
}
