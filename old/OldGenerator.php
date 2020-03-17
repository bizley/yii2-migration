<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\Column;
use bizley\migration\table\ColumnFactory;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\ForeignKeyData;
use bizley\migration\table\Index;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\Structure;
use PDO;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\View;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\TableSchema;
use yii\helpers\FileHelper;

use function count;
use function get_class;
use function in_array;
use function is_array;

class OldGenerator extends Component
{
    /** @var Connection DB connection */
    public $db;

    /** @var string Table name to be generated (before prefix) */
    public $tableName;

    /** @var string Migration class name */
    public $className;

    /** @var View View used in controller */
    public $view;

    /** @var bool Table prefix flag */
    public $useTablePrefix = true;

    /** @var string Create migration template file */
    public $templateFileCreate;

    /** @var string Update migration template file */
    public $templateFileUpdate;

    /** @var string|array Migration namespaces */
    public $namespace;

    /** @var bool Whether to use general column schema instead of database specific */
    public $generalSchema = true;

    /** @var string */
    public $tableOptionsInit;

    /** @var string */
    public $tableOptions;

    /** @var array */
    public $suppressForeignKey = [];

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->db instanceof Connection === false) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\\db\\Connection!");
        }

        if ($this->namespace !== null && is_array($this->namespace) === false) {
            $this->namespace = [$this->namespace];
        }
    }

    /** @var TableSchema */
    private $tableSchema;

    public function getTableSchema(): ?TableSchema
    {
        if ($this->tableSchema === null) {
            $this->tableSchema = $this->db->getTableSchema($this->tableName);
        }

        return $this->tableSchema;
    }

    protected function getTablePrimaryKey(): PrimaryKey
    {
        $data = [];

        /** @var $constraint Constraint */
        $constraint = $this->db->schema->getTablePrimaryKey($this->tableName, true);
        if ($constraint) {
            $data = [
                'columns' => $constraint->columnNames,
                'name' => $constraint->name,
            ];
        }

        return new PrimaryKey($data);
    }

    /**
     * @param Index[] $indexes
     * @param string|null $schema
     * @return Column[]
     * @throws InvalidConfigException
     */
    protected function getTableColumns(array $indexes = [], string $schema = null): array
    {
        $columns = [];

        if ($this->getTableSchema() instanceof TableSchema) {
            $indexData = count($indexes) ? $indexes : $this->getTableIndexes();

            try {
                $version = $this->db->getSlavePdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (Throwable $exception) {
                $version = null;
            }

            foreach ($this->getTableSchema()->columns as $column) {
                $isUnique = false;

                foreach ($indexData as $index) {
                    if ($index->unique && $index->columns[0] === $column->name && count($index->columns) === 1) {
                        $isUnique = true;
                        break;
                    }
                }

                $columns[$column->name]
                    = ColumnFactory::build([
                        'schema' => $schema,
                        'name' => $column->name,
                        'type' => $column->type,
                        'defaultMapping' => $this->db->schema->queryBuilder->typeMap[$column->type],
                        'engineVersion' => $version,
                        'size' => $column->size,
                        'precision' => $column->precision,
                        'scale' => $column->scale,
                        'isNotNull' => $column->allowNull ? null : true,
                        'isUnique' => $isUnique,
                        'check' => null,
                        'default' => $column->defaultValue,
                        'isPrimaryKey' => $column->isPrimaryKey,
                        'autoIncrement' => $column->autoIncrement,
                        'isUnsigned' => $column->unsigned,
                        'comment' => $column->comment ?: null,
                    ]);
            }
        }

        return $columns;
    }

    /**
     * @return ForeignKey[]
     */
    protected function getTableForeignKeys(): array
    {
        $data = [];

        $fks = $this->db->schema->getTableForeignKeys($this->tableName, true);

        /** @var $fk ForeignKeyConstraint */
        foreach ($fks as $fk) {
            $data[$fk->name]
                = new ForeignKey([
                    'name' => $fk->name,
                    'columns' => $fk->columnNames,
                    'refTable' => $fk->foreignTableName,
                    'refColumns' => $fk->foreignColumnNames,
                    'onDelete' => $fk->onDelete,
                    'onUpdate' => $fk->onUpdate,
                ]);
        }

        return $data;
    }

    /**
     * @return Index[]
     */
    protected function getTableIndexes(): array
    {
        $data = [];

        $indexes = $this->db->schema->getTableIndexes($this->tableName, true);

        /** @var $index IndexConstraint */
        foreach ($indexes as $index) {
            if ($index->isPrimary === false) {
                $data[$index->name]
                    = new Index([
                        'name' => $index->name,
                        'unique' => $index->isUnique,
                        'columns' => $index->columnNames
                    ]);
            }
        }

        return $data;
    }

    /** @var Structure */
    private $tableStructure;
    /** @var array */
    private $suppressedForeignKeys = [];

    /**
     * @return Structure
     * @throws InvalidConfigException
     */
    public function getTableStructure(): Structure
    {
        if ($this->tableStructure === null) {
            $foreignKeys = $this->getTableForeignKeys();

            foreach ($foreignKeys as $foreignKeyName => $foreignKey) {
                if (in_array($foreignKey->referencedTable, $this->suppressForeignKey, true)) {
                    $this->suppressedForeignKeys[]
                        = new ForeignKeyData([
                            'foreignKey' => $foreignKey,
                            'table'
                                => new Structure([
                                    'name' => $this->tableName,
                                    'usePrefix' => $this->useTablePrefix,
                                    'dbPrefix' => $this->db->tablePrefix,
                                ]),
                        ]);
                    unset($foreignKeys[$foreignKeyName]);
                }
            }

            $indexes = $this->getTableIndexes();

            $this->tableStructure
                = new Structure([
                    'name' => $this->tableName,
                    'schema' => get_class($this->db->schema),
                    'generalSchema' => $this->generalSchema,
                    'usePrefix' => $this->useTablePrefix,
                    'dbPrefix' => $this->db->tablePrefix,
                    'primaryKey' => $this->getTablePrimaryKey(),
                    'foreignKeys' => $foreignKeys,
                    'indexes' => $indexes,
                    'tableOptionsInit' => $this->tableOptionsInit,
                    'tableOptions' => $this->tableOptions,
                ]);

            $this->tableStructure->columns = $this->getTableColumns($indexes, $this->tableStructure->getSchema());
        }

        return $this->tableStructure;
    }

    public function getSuppressedForeignKeys(): array
    {
        return $this->suppressedForeignKeys;
    }

    public function getNormalizedNamespace(): ?string
    {
        return !empty($this->namespace) ? FileHelper::normalizePath(reset($this->namespace), '\\') : null;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function generateMigration(): string
    {
        return $this->view->renderFile(
            Yii::getAlias($this->templateFileCreate),
            [
                'table' => $this->getTableStructure(),
                'className' => $this->className,
                'namespace' => $this->getNormalizedNamespace()
            ]
        );
    }
}
