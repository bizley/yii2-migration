<?php

namespace bizley\migration;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\db\Connection;
use yii\db\TableSchema;

/**
 * Extractor class.
 * Gathers information about DB schema and migration files.
 *
 * @author Paweł Bizley Brzozowski
 * @version 2.1.2
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 *
 * @property TableSchema $tableSchema
 * @property array $structure
 */
class Extractor extends Component
{
    /**
     * @var Connection DB connection.
     */
    public $db;

    /**
     * @var string Table name to be generated (before prefix).
     */
    public $tableName;

    /**
     * @var string Migration class name.
     */
    public $className;

    /**
     * @var View View used in controller.
     */
    public $view;

    /**
     * @var bool Table prefix flag.
     */
    public $useTablePrefix;

    /**
     * @var string File template.
     */
    public $templateFile;

    /**
     * @var string File update template.
     */
    public $templateFileUpdate;

    /**
     * @var string Migration namespace.
     */
    public $namespace;

    /**
     * @var bool Whether to use general column schema instead of database specific.
     */
    public $generalSchema = false;

    /**
     * Checks if DB connection is passed.
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!($this->db instanceof Connection)) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\\db\\Connection!");
        }
    }

    protected $_tableSchema;

    /**
     * Returns table schema.
     * @return TableSchema
     */
    public function getTableSchema()
    {
        if ($this->_tableSchema === null) {
            $this->_tableSchema = $this->db->getTableSchema($this->tableName);
        }
        return $this->_tableSchema;
    }

    /**
     * If $useTablePrefix equals true then the table name will contain the prefix format.
     * @param string $tableName the table name to generate.
     * @return string
     */
    protected function generateTableName($tableName)
    {
        if (!$this->useTablePrefix) {
            return $tableName;
        }
        return '{{%' . $tableName . '}}';
    }

    /**
     * Checks if table schema is available.
     * @throws InvalidParamException
     */
    public function checkSchema()
    {
        if (!$this->tableSchema) {
            throw new InvalidParamException("Cannot find schema for '{$this->tableName}' table!");
        }
    }

    /**
     * Returns table structure.
     * @return array
     * @throws InvalidParamException
     */
    public function getStructure()
    {
        $this->checkSchema();
        return [
            'table' => $this->tableName,
            'pk' => $this->getTablePrimaryKey(),
            'columns' => $this->getTableColumns(),
            'fks' => $this->getTableForeignKeys(),
            'uidxs' => $this->getTableUniqueIndexes(),
            'idxs' => $this->getTableIndexes(),
        ];
    }

    /**
     * Returns table primary key.
     * @return array
     */
    protected function getTablePrimaryKey()
    {
        if (method_exists($this->db->schema, 'getTablePrimaryKey')) {
            /* @var $constraint \yii\db\Constraint */
            $constraint = $this->db->schema->getTablePrimaryKey($this->tableName, true);
            return [
                'columnNames' => $constraint->columnNames,
                'name' => $constraint->name,
            ];
        }
        if ($this->tableSchema instanceof TableSchema) {
            $pk = $this->tableSchema->primaryKey;
            return [
                'columnNames' => $pk,
                'name' => null,
            ];
        }
        return [];
    }

    /**
     * Returns table unique indexes.
     * @return array
     */
    protected function getTableUniqueIndexes()
    {
        try {
            return $this->db->schema->findUniqueIndexes($this->tableSchema);
        } catch (NotSupportedException $exc) {
            return [];
        }
    }

    /**
     * Returns table indexes.
     * @return array
     * @since 2.2.2
     */
    protected function getTableIndexes()
    {
        try {
            return $this->db->schema->findUniqueIndexes($this->tableSchema);
        } catch (NotSupportedException $exc) {
            return [];
        }
    }

    /**
     * Prepares append SQL based on schema.
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string
     */
    public function prepareSchemaAppend($primaryKey, $autoIncrement)
    {
        $schema = $this->db->schema;
        switch ($schema::className()) {
            case 'yii\db\mssql\Schema':
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;
            case 'yii\db\oci\Schema':
            case 'yii\db\pgsql\Schema':
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;
            case 'yii\db\sqlite\Schema':
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;
            case 'yii\db\cubrid\Schema':
            case 'yii\db\mysql\Schema':
            default:
                $append = trim(($autoIncrement ? 'AUTO_INCREMENT ' : '') . ($primaryKey ? 'PRIMARY KEY' : ''));
        }
        return empty($append) ? null : $append;
    }

    /**
     * Returns columns structure.
     * @return array
     */
    protected function getTableColumns()
    {
        $columns = [];
        if ($this->tableSchema instanceof TableSchema) {
            $uniqueIndexes = $this->getTableUniqueIndexes();
            foreach ($this->tableSchema->columns as $column) {
                $isUnique = false;
                foreach ($uniqueIndexes as $uIndex) {
                    if ($uIndex[0] === $column->name && count($uIndex) === 1) {
                        $isUnique = true;
                        break;
                    }
                }
                $columns[$column->name] = [
                    'type' => $column->type,
                    'length' => $column->size,
                    'isNotNull' => $column->allowNull ? null : true,
                    'isUnique' => $isUnique,
                    'check' => null,
                    'default' => $column->defaultValue,
                    'append' => $this->prepareSchemaAppend($column->isPrimaryKey, $column->autoIncrement),
                    'isUnsigned' => $column->unsigned,
                    'comment' => $column->comment,
                ];
            }
        }
        return $columns;
    }

    /**
     * Returns foreign keys structure.
     * @return array
     */
    protected function getTableForeignKeys()
    {
        $keys = [];
        if ($this->tableSchema instanceof TableSchema) {
            foreach ($this->tableSchema->foreignKeys as $name => $key) {
                $keys[$name] = $key;
            }
        }
        return $keys;
    }
}
