<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function array_key_exists;
use function mb_strlen;
use function sprintf;
use function strpos;
use function substr;

class Structure extends BaseObject
{
    public const SCHEMA_MSSQL = 'mssql';
    public const SCHEMA_OCI = 'oci';
    public const SCHEMA_PGSQL = 'pgsql';
    public const SCHEMA_SQLITE = 'sqlite';
    public const SCHEMA_CUBRID = 'cubrid';
    public const SCHEMA_MYSQL = 'mysql';
    public const SCHEMA_UNSUPPORTED = 'unsupported';

    /** @var string */
    public $name;

    /** @var PrimaryKey */
    public $primaryKey;

    /** @var Column[] */
    public $columns = [];

    /** @var Index[] */
    public $indexes = [];

    /**
     * @var ForeignKey[]
     */
    private $foreignKeys = [];

    /** @var bool */
    public $generalSchema = true;

    /** @var bool */
    public $usePrefix = true;

    /** @var string */
    public $dbPrefix;

    /** @var string|null */
    public $tableOptionsInit;

    /** @var string|null */
    public $tableOptions;

    /** @var string */
    protected $schema;

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function setForeignKeys(array $foreignKeys): void
    {
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * Returns schema type.
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    public function isSchemaSQLite(): bool
    {
        return $this->getSchema() === self::SCHEMA_SQLITE;
    }

    /**
     * Returns schema code based on its class name.
     * @param null|string $schemaClass
     * @return string
     */
    public static function identifySchema(?string $schemaClass): string
    {
        switch ($schemaClass) {
            case 'yii\db\mssql\Schema':
                return self::SCHEMA_MSSQL;

            case 'yii\db\oci\Schema':
                return self::SCHEMA_OCI;

            case 'yii\db\pgsql\Schema':
                return self::SCHEMA_PGSQL;

            case 'yii\db\sqlite\Schema':
                return self::SCHEMA_SQLITE;

            case 'yii\db\cubrid\Schema':
                return self::SCHEMA_CUBRID;

            case 'yii\db\mysql\Schema':
                return self::SCHEMA_MYSQL;

            default:
                return self::SCHEMA_UNSUPPORTED;
        }
    }

    /**
     * Sets schema type based on the currently used schema class.
     * @param string|null $schemaClass
     */
    public function setSchema(?string $schemaClass): void
    {
        $this->schema = static::identifySchema($schemaClass);
    }

    /**
     * Renders table name.
     * @return string
     */
    public function renderName(): string
    {
        $tableName = $this->name;

        if (!$this->usePrefix) {
            return $tableName;
        }

        if ($this->dbPrefix && strpos($this->name, $this->dbPrefix) === 0) {
            $tableName = substr($this->name, mb_strlen($this->dbPrefix, 'UTF-8'));
        }

        return '{{%' . $tableName . '}}';
    }

    /**
     * Renders the migration structure.
     * @return string
     */
    public function render(): string
    {
        return $this->renderTable() . $this->renderPk() . $this->renderIndexes() . $this->renderForeignKeys() . "\n";
    }

    /**
     * Renders the table.
     * @return string
     */
    public function renderTable(): string
    {
        $output = '';

        if ($this->tableOptionsInit !== null) {
            $output .= "        {$this->tableOptionsInit}\n\n";
        }

        $output .= sprintf('        $this->createTable(\'%s\', [', $this->renderName());

        foreach ($this->columns as $column) {
            $output .= "\n" . $column->render($this);
        }

        $output .= "\n" . sprintf(
            '        ]%s);',
            $this->tableOptions !== null ? ", {$this->tableOptions}" : ''
        ) . "\n";

        return $output;
    }

    /**
     * Renders the primary key.
     * @return string
     */
    public function renderPk(): string
    {
        $output = '';

        if ($this->primaryKey->isComposite()) {
            $output .= "\n" . $this->primaryKey->render($this);
        }

        return $output;
    }

    /**
     * Renders the indexes.
     * @return string
     */
    public function renderIndexes(): string
    {
        $output = '';

        if ($this->indexes) {
            foreach ($this->indexes as $index) {
                foreach ($this->foreignKeys as $foreignKey) {
                    if ($foreignKey->name === $index->name) {
                        continue 2;
                    }
                }

                $output .= "\n" . $index->render($this);
            }
        }

        return $output;
    }

    /**
     * Renders the foreign keys.
     * @return string
     */
    public function renderForeignKeys(): string
    {
        $output = '';

        if ($this->foreignKeys) {
            foreach ($this->foreignKeys as $foreignKey) {
                $output .= "\n" . $foreignKey->render($this);
            }
        }

        return $output;
    }

    /**
     * Builds table structure based on the list of changes from the Updater.
     * @param StructureChange[] $changes
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function applyChanges(array $changes): void
    {
        /** @var $change StructureChange */
        foreach ($changes as $change) {
            if ($change instanceof StructureChange === false) {
                throw new InvalidArgumentException('You must provide array of Change objects.');
            }

            $value = $change->getValue();

            switch ($change->method) {
                case 'createTable':
                    /* @var $column Column */
                    foreach ($value as $column) {
                        $this->columns[$column->name] = $column;

                        if ($column->isPrimaryKey || $column->isPrimaryKeyInfoAppended()) {
                            if ($this->primaryKey === null) {
                                $this->primaryKey = new PrimaryKey(['columns' => [$column->name]]);
                            } else {
                                $this->primaryKey->addColumn($column->name);
                            }
                        }
                    }
                    break;

                case 'addColumn':
                    $this->columns[$value->name] = $value;

                    if ($value->isPrimaryKey || $value->isPrimaryKeyInfoAppended()) {
                        if ($this->primaryKey === null) {
                            $this->primaryKey = new PrimaryKey(['columns' => [$value->name]]);
                        } else {
                            $this->primaryKey->addColumn($value->name);
                        }
                    }
                    break;

                case 'dropColumn':
                    unset($this->columns[$value]);
                    break;

                case 'renameColumn':
                    if (array_key_exists($value['old'], $this->columns)) {
                        $this->columns[$value['new']] = $this->columns[$value['old']];
                        $this->columns[$value['new']]->name = $value['new'];

                        unset($this->columns[$value['old']]);
                    }
                    break;

                case 'alterColumn':
                    $this->columns[$value->name] = $value;
                    break;

                case 'addPrimaryKey':
                    $this->primaryKey = $value;

                    foreach ($this->primaryKey->columns as $column) {
                        if (array_key_exists($column, $this->columns)) {
                            if (empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append = $this->columns[$column]->prepareSchemaAppend(
                                    true,
                                    false
                                );
                            } elseif (!$this->columns[$column]->isPrimaryKeyInfoAppended()) {
                                $this->columns[$column]->append .= ' ' . $this->columns[$column]->prepareSchemaAppend(
                                    true,
                                    false
                                );
                            }
                        }
                    }
                    break;

                case 'dropPrimaryKey':
                    if ($this->primaryKey !== null) {
                        foreach ($this->primaryKey->columns as $column) {
                            if (array_key_exists($column, $this->columns) && !empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append = $this->columns[$column]->removeAppendedPrimaryKeyInfo();
                            }
                        }
                    }

                    $this->primaryKey = null;
                    break;

                case 'addForeignKey':
                    $this->foreignKeys[$value->name] = $value;
                    break;

                case 'dropForeignKey':
                    unset($this->foreignKeys[$value]);
                    break;

                case 'createIndex':
                    $this->indexes[$value->name] = $value;
                    if (
                        $value->unique
                        && array_key_exists($value->columns[0], $this->columns)
                        && count($value->columns) === 1
                    ) {
                        $this->columns[$value->columns[0]]->isUnique = true;
                    }
                    break;

                case 'dropIndex':
                    if (
                        $this->indexes[$value]->unique
                        && count($this->indexes[$value]->columns) === 1
                        && array_key_exists($this->indexes[$value]->columns[0], $this->columns)
                        && $this->columns[$this->indexes[$value]->columns[0]]->isUnique
                    ) {
                        $this->columns[$this->indexes[$value]->columns[0]]->isUnique = false;
                    }
                    unset($this->indexes[$value]);
                    break;

                case 'addCommentOnColumn':
                    if (array_key_exists($value->name, $this->columns)) {
                        $this->columns[$value->name]->comment = $value->comment;
                    }
                    break;

                case 'dropCommentFromColumn':
                    if (array_key_exists($value, $this->columns)) {
                        $this->columns[$value]->comment = null;
                    }
            }
        }
    }
}
