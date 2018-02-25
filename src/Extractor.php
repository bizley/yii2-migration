<?php

namespace bizley\migration;

use bizley\migration\table\TableColumn;
use bizley\migration\table\TableForeignKey;
use bizley\migration\table\TableIndex;
use bizley\migration\table\TablePrimaryKey;
use bizley\migration\table\TableStructure;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\db\Connection;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

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
 * @property TableStructure $table
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

    const GENERIC_PRIMARY_KEY = 'PRIMARYKEY';

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
     * TODO wywalic
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
     * TODO wywalic
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
     * @return TablePrimaryKey
     */
    protected function getTablePrimaryKey()
    {
        $data = [];
        if (method_exists($this->db->schema, 'getTablePrimaryKey')) {
            /* @var $constraint \yii\db\Constraint */
            $constraint = $this->db->schema->getTablePrimaryKey($this->tableName, true);
            $data = [
                'columns' => $constraint->columnNames,
                'name' => $constraint->name,
            ];
        } elseif ($this->tableSchema instanceof TableSchema) {
            $pk = $this->tableSchema->primaryKey;
            $data = [
                'columns' => $pk,
                'name' => self::GENERIC_PRIMARY_KEY,
            ];
        }
        return new TablePrimaryKey($data);
    }

    /**
     * Returns columns structure.
     * @param $indexes TableIndex[]
     * @return TableColumn[]
     */
    protected function getTableColumns($indexes = [])
    {
        $columns = [];
        if ($this->tableSchema instanceof TableSchema) {
            $indexData = !empty($indexes) ? $indexes : $this->getTableIndexes();
            foreach ($this->tableSchema->columns as $column) {
                $isUnique = false;
                foreach ($indexData as $index) {
                    if ($index->unique && $index->columns[0] === $column->name && count($index->columns) === 1) {
                        $isUnique = true;
                        break;
                    }
                }
                $columns[] = new TableColumn([
                    'name' => $column->name,
                    'type' => $column->type,
                    'length' => $column->size,
                    'isNotNull' => $column->allowNull ? null : true,
                    'isUnique' => $isUnique,
                    'check' => null,
                    'default' => $column->defaultValue,
                    'append' => $this->prepareSchemaAppend($column->isPrimaryKey, $column->autoIncrement),
                    'isUnsigned' => $column->unsigned,
                    'comment' => $column->comment,
                ]);
            }
        }
        return $columns;
    }

    /**
     * Returns foreign keys structure.
     * @return TableForeignKey[]
     */
    protected function getTableForeignKeys()
    {
        $data = [];
        if (method_exists($this->db->schema, 'getTableForeignKeys')) {
            $fks = $this->db->schema->getTableForeignKeys($this->tableName, true);
            /* @var $fk \yii\db\ForeignKeyConstraint */
            foreach ($fks as $fk) {
                $data[] = new TableForeignKey([
                    'name' => $fk->name,
                    'columns' => $fk->columnNames,
                    'refTable' => $fk->foreignTableName,
                    'refColumns' => $fk->foreignColumnNames,
                    'onDelete' => $fk->onDelete,
                    'onUpdate' => $fk->onUpdate,
                ]);
            }
        } elseif ($this->tableSchema instanceof TableSchema) {
            foreach ($this->tableSchema->foreignKeys as $name => $key) {
                $fk = new TableForeignKey([
                    'name' => $name,
                    'refTable' => ArrayHelper::remove($key, 0),
                    'onDelete' => null,
                    'onUpdate' => null,
                ]);
                foreach ($key as $col => $ref) {
                    $fk->columns[] = $col;
                    $fk->refColumns[] = $ref;
                }
                $data[] = $fk;
            }
        }
        return $data;
    }

    /**
     * Returns table unique indexes.
     * @return array
     * TODO: wywalic
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
     * @return TableIndex[]
     * @since 2.2.2
     */
    protected function getTableIndexes()
    {
        $data = [];
        if (method_exists($this->db->schema, 'getTableIndexes')) {
            $idxs = $this->db->schema->getTableIndexes($this->tableName, true);
            /* @var $idx \yii\db\IndexConstraint */
            foreach ($idxs as $idx) {
                if (!$idx->isPrimary) {
                    $data[] = new TableIndex([
                        'name' => $idx->name,
                        'unique' => $idx->isUnique,
                        'columns' => $idx->columnNames
                    ]);
                }
            }
        } else {
            try {
                $uidxs = $this->db->schema->findUniqueIndexes($this->tableSchema);
                foreach ($uidxs as $name => $cols) {
                    $data[] = new TableIndex([
                        'name' => $name,
                        'unique' => false,
                        'columns' => $cols
                    ]);
                }
            } catch (NotSupportedException $exc) {}
        }
        return $data;
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

    private $_table;

    /**
     * Returns table data
     * @return TableStructure
     */
    public function getTable()
    {
        if ($this->_table === null) {
            $this->_table = new TableStructure(['name' => $this->tableName]);
            $indexes = $this->getTableIndexes();
            $this->_table->primaryKey = $this->getTablePrimaryKey();
            $this->_table->columns = $this->getTableColumns($indexes);
            $this->_table->foreignKeys = $this->getTableForeignKeys();
            $this->_table->indexes = $indexes;
        }
        return $this->_table;
    }
}
