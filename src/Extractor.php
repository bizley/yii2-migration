<?php

namespace bizley\migration;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\View;
use yii\db\Connection;
use yii\db\TableSchema;

/**
 * Extractor class.
 * Gathers information about DB schema and migration files.
 *
 * @author Paweł Bizley Brzozowski
 * @version 2.0
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
     * @var string table name to be generated (before prefix).
     */
    public $tableName;

    /**
     * @var string migration class name.
     */
    public $className;

    /**
     * @var View view used in controller.
     */
    public $view;

    /**
     * @var bool table prefix flag.
     */
    public $useTablePrefix;

    /**
     * @var string file template.
     */
    public $templateFile;

    /**
     * @var string file update template.
     */
    public $templateFileUpdate;

    /**
     * @var string migration namespace.
     */
    public $namespace;

    /**
     * @var bool whether to use general column schema instead of database specific.
     */
    public $generalSchema = 0;

    protected $schemaAutoIncrement = '';

    /**
     * Checks if DB connection is passed.
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!($this->db instanceof Connection)) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\db\Connection!");
        }
        $schema = $this->db->schema;
        switch ($schema::className()) {
            case 'yii\db\sqlite\Schema':
                $this->schemaAutoIncrement = 'AUTOINCREMENT';
                break;
            case 'yii\db\cubrid\Schema':
            case 'yii\db\mysql\Schema':
                $this->schemaAutoIncrement = 'AUTO_INCREMENT';
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
     * If $useTablePrefix equals true, then the table name will contain the
     * prefix format.
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
     */
    public function getStructure()
    {
        $this->checkSchema();
        return [
            'table' => $this->tableName,
            'pk' => $this->tablePrimaryKey,
            'columns' => $this->tableColumns,
            'fks' => $this->tableForeignKeys
        ];
    }

    /**
     * Returns table primary key.
     * @return array
     */
    protected function getTablePrimaryKey()
    {
        $pk = [];
        if ($this->tableSchema instanceof TableSchema) {
            $pk = $this->tableSchema->primaryKey;
        }
        return $pk;
    }

    /**
     * Returns columns structure.
     * @return array
     */
    protected function getTableColumns()
    {
        $columns = [];
        if ($this->tableSchema instanceof TableSchema) {
            $uniqueIndexes = $this->db->schema->findUniqueIndexes($this->tableSchema);
            foreach ($this->tableSchema->columns as $column) {
                $isUnique = false;
                foreach ($uniqueIndexes as $uIndex) {
                    if ($uIndex[0] == $column->name && count($uIndex) === 1) {
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
                    'append' => $column->autoIncrement ? $this->schemaAutoIncrement : null,
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
