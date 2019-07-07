<?php

namespace bizley\migration\table;

use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * Class TableStructure
 * @package bizley\migration\table
 *
 * @property string $schema
 */
class TableStructure extends Object
{
    const SCHEMA_MSSQL = 'mssql';
    const SCHEMA_OCI = 'oci';
    const SCHEMA_PGSQL = 'pgsql';
    const SCHEMA_SQLITE = 'sqlite';
    const SCHEMA_CUBRID = 'cubrid';
    const SCHEMA_MYSQL = 'mysql';
    const SCHEMA_UNSUPPORTED = 'unsupported';

    /**
     * @var string
     */
    public $name;

    /**
     * @var TablePrimaryKey
     */
    public $primaryKey;

    /**
     * @var TableColumn[]
     */
    public $columns = [];

    /**
     * @var TableIndex[]
     */
    public $indexes = [];

    /**
     * @var TableForeignKey[]
     */
    public $foreignKeys = [];

    /**
     * @var bool
     */
    public $generalSchema = true;

    /**
     * @var bool
     */
    public $usePrefix = true;

    /**
     * @var string
     */
    public $dbPrefix;

    /**
     * @var string|null
     * @since 2.3.4
     */
    public $tableOptionsInit;

    /**
     * @var string|null
     * @since 2.3.4
     */
    public $tableOptions;

    /**
     * Returns schema type.
     * @return string
     */
    public function getSchema()
    {
        return $this->_schema;
    }

    protected $_schema;

    /**
     * Returns schema code based on its class name.
     * @param null|string $schemaClass
     * @return string
     * @since 2.4
     */
    public static function identifySchema($schemaClass)
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
    public function setSchema($schemaClass)
    {
        $this->_schema = static::identifySchema($schemaClass);
    }

    /**
     * Renders table name.
     * @return bool|string
     */
    public function renderName()
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
    public function render()
    {
        return $this->renderTable() . $this->renderPk() . $this->renderIndexes() . $this->renderForeignKeys() . "\n";
    }

    /**
     * Renders the table.
     * @return string
     */
    public function renderTable()
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
    public function renderPk()
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
    public function renderIndexes()
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
    public function renderForeignKeys()
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
     * @param TableChange[] $changes
     * @throws InvalidParamException
     */
    public function applyChanges($changes)
    {
        /* @var $change TableChange */
        foreach ($changes as $change) {
            if (!$change instanceof TableChange) {
                throw new InvalidParamException('You must provide array of TableChange objects.');
            }

            switch ($change->method) {
                case 'createTable':
                    /* @var $column TableColumn */
                    foreach ($change->value as $column) {
                        $this->columns[$column->name] = $column;

                        if ($column->isPrimaryKey || $column->isColumnAppendPK()) {
                            if ($this->primaryKey === null) {
                                $this->primaryKey = new TablePrimaryKey(['columns' => [$column->name]]);
                            } else {
                                $this->primaryKey->addColumn($column->name);
                            }
                        }
                    }
                    break;

                case 'addColumn':
                    $this->columns[$change->value->name] = $change->value;

                    if ($change->value->isPrimaryKey || $change->value->isColumnAppendPK()) {
                        if ($this->primaryKey === null) {
                            $this->primaryKey = new TablePrimaryKey(['columns' => [$change->value->name]]);
                        } else {
                            $this->primaryKey->addColumn($change->value->name);
                        }
                    }
                    break;

                case 'dropColumn':
                    unset($this->columns[$change->value]);
                    break;

                case 'renameColumn':
                    if (isset($this->columns[$change->value['old']])) {
                        $this->columns[$change->value['new']] = $this->columns[$change->value['old']];
                        $this->columns[$change->value['new']]->name = $change->value['new'];

                        unset($this->columns[$change->value['old']]);
                    }
                    break;

                case 'alterColumn':
                    $this->columns[$change->value->name] = $change->value;
                    break;

                case 'addPrimaryKey':
                    $this->primaryKey = $change->value;

                    foreach ($this->primaryKey->columns as $column) {
                        if (isset($this->columns[$column])) {
                            if (empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append = $this->columns[$column]->prepareSchemaAppend(
                                    true,
                                    false
                                );
                            } elseif (!$this->columns[$column]->isColumnAppendPK()) {
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
                            if (isset($this->columns[$column]) && !empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append = $this->columns[$column]->removePKAppend();
                            }
                        }
                    }

                    $this->primaryKey = null;
                    break;

                case 'addForeignKey':
                    $this->foreignKeys[$change->value->name] = $change->value;
                    break;

                case 'dropForeignKey':
                    unset($this->foreignKeys[$change->value]);
                    break;

                case 'createIndex':
                    $this->indexes[$change->value->name] = $change->value;
                    if ($change->value->unique
                        && isset($this->columns[$change->value->columns[0]])
                        && count($change->value->columns) === 1
                    ) {
                        $this->columns[$change->value->columns[0]]->isUnique = true;
                    }
                    break;

                case 'dropIndex':
                    if ($this->indexes[$change->value]->unique
                        && count($this->indexes[$change->value]->columns) === 1
                        && isset($this->columns[$this->indexes[$change->value]->columns[0]])
                        && $this->columns[$this->indexes[$change->value]->columns[0]]->isUnique
                    ) {
                        $this->columns[$this->indexes[$change->value]->columns[0]]->isUnique = false;
                    }
                    unset($this->indexes[$change->value]);
                    break;

                case 'addCommentOnColumn':
                    if (isset($this->columns[$change->value->name])) {
                        $this->columns[$change->value->name]->comment = $change->value->comment;
                    }
                    break;

                case 'dropCommentFromColumn':
                    if (isset($this->columns[$change->value])) {
                        $this->columns[$change->value]->comment = null;
                    }
            }
        }
    }
}
