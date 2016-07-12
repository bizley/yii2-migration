<?php

namespace bizley\migration\components;

use Exception;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\View;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

/**
 * Migration file generator.
 * 
 * @author Paweł Bizley Brzozowski
 * @version 1.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 * 
 * ONLY MYSQL NOW
 * 
 * @property TableSchema $tableSchema
 */
class Generator extends Component
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
     * @var boolean Table prefix flag.
     */
    public $useTablePrefix;
    
    /**
     * @var string File template.
     */
    public $templateFile;
    
    /**
     * @var TableSchema Table schema.
     */
    protected $_tableSchema;
    
    /**
     * Checks if DB connection is passed.
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!($this->db instanceof Connection)) {
            throw new InvalidConfigException('Parameter db must be an instance of yii\db\Connection!');
        }
    }

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
     * If `useTablePrefix` equals true, then the table name will contain the
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
     * Generates migration content or echoes exception message.
     * @return string
     */
    public function generateMigration()
    {
        try {
            $this->checkSchema();
            $params = [
                'tableName'   => $this->generateTableName($this->tableName),
                'className'   => $this->className,
                'columns'     => $this->prepareColumnsDefinitions(),
                'foreignKeys' => $this->prepareForeignKeysDefinitions(),
            ];
            return $this->view->renderFile(Yii::getAlias($this->templateFile), $params);
        } catch (Exception $exc) {
            echo 'Exception: ' . $exc->getMessage() . "\n\n";
        }
    }
    
    /**
     * Checks if table schema is available.
     * @throws InvalidParamException
     */
    public function checkSchema()
    {
        if (!$this->tableSchema) {
            throw new InvalidParamException('Cannot find schema for ' . $this->tableName . ' table!');
        }
    }
    
    /**
     * Prepares definitions of columns.
     * @return array
     */
    public function prepareColumnsDefinitions()
    {
        $columns = [];
        if ($this->tableSchema instanceof TableSchema) {
            foreach ($this->tableSchema->columns as $column) {
                $columns[$column->name] = $this->renderColumnDefinition($column);
            }
        }
        return $columns;
    }
    
    /**
     * Prepares definitions of foreign keys.
     * @return array
     */
    public function prepareForeignKeysDefinitions()
    {
        $keys = [];
        if ($this->tableSchema instanceof TableSchema) {
            foreach ($this->tableSchema->foreignKeys as $key) {
                $keys[] = $this->renderKeyDefinition($key);
            }
        }
        return $keys;
    }
    
    /**
     * Returns size value from ColumnSchema.
     * @param ColumnSchema $column
     * @return mixed
     */
    public function renderSize(ColumnSchema $column)
    {
        return $column->size ?: null;
    }
    
    /**
     * Returns scale value from ColumnSchema.
     * @param ColumnSchema $column
     * @return mixed
     */
    public function renderScale(ColumnSchema $column)
    {
        return $column->scale ?: null;
    }
    
    /**
     * Returns precision value from ColumnSchema.
     * @param ColumnSchema $column
     * @return mixed
     */
    public function renderPrecision(ColumnSchema $column)
    {
        return $column->precision ?: null;
    }
    
    /**
     * Returns column definition based on ColumnSchema.
     * @param ColumnSchema $column
     * @return string
     */
    public function renderColumnDefinition(ColumnSchema $column)
    {
        $definition = '';
        switch ($column->type) {
            case Schema::TYPE_CHAR:
                $definition .= '->char(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_STRING:
                $definition .= '->string(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_TEXT:
                $definition .= '->text()';
                break;
            case Schema::TYPE_SMALLINT:
                $definition .= '->smallInteger(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_INTEGER:
                $definition .= '->integer(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_BIGINT:
                $definition .= '->bigInteger(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_FLOAT:
                $definition .= '->float(' . $this->renderPrecision($column) . ')';
                break;
            case Schema::TYPE_DOUBLE:
                $definition .= '->double(' . $this->renderPrecision($column) . ')';
                break;
            case Schema::TYPE_DECIMAL:
                $definition .= '->decimal(' . $this->renderPrecision($column) . ', ' . $this->renderScale($column) . ')';
                break;
            case Schema::TYPE_DATETIME:
                $definition .= '->dateTime(' . $this->renderPrecision($column) . ')';
                break;
            case Schema::TYPE_TIMESTAMP:
                $definition .= '->timestamp(' . $this->renderPrecision($column) . ')';
                break;
            case Schema::TYPE_TIME:
                $definition .= '->time(' . $this->renderPrecision($column) . ')';
                break;
            case Schema::TYPE_DATE:
                $definition .= '->date()';
                break;
            case Schema::TYPE_BINARY:
                $definition .= '->binary(' . $this->renderSize($column) . ')';
                break;
            case Schema::TYPE_BOOLEAN:
                $definition .= '->boolean()';
                break;
            case Schema::TYPE_MONEY:
                $definition .= '->money(' . $this->renderPrecision($column) . ', ' . $this->renderScale($column) . ')';
                break;
        }
        if ($column->unsigned) {
            $definition .= '->unsigned()';
        }
        if (!$column->allowNull) {
            $definition .= '->notNull()';
        }
        if ($column->defaultValue !== null) {
            if ($column->defaultValue instanceof Expression) {
                $definition .= '->defaultExpression(\'' . $column->defaultValue->expression . '\')';
            } else {
                $definition .= '->defaultValue(\'' . $column->defaultValue . '\')';
            }
        }
        if ($column->comment) {
            $definition .= '->comment(\'' . $column->comment . '\')';
        }
        if ($column->isPrimaryKey) {
            $definition .= '->append(\'PRIMARY KEY\')';
        }
        if ($column->autoIncrement) {
            $definition .= '->append(\'AUTO_INCREMENT\')';
        }
        
        return $definition;
    }
    
    /**
     * Returns foreign key definition based on key array.
     * @param array $key
     * @return string
     */
    public function renderKeyDefinition($key)
    {
        $refTable = ArrayHelper::remove($key, 0);
        $column = key($key);
        $refColumn = current($key);
        $name = $this->generateForeignKeyName($column);
        
        return implode(', ', [
            "'$name'",
            "'{$this->generateTableName($this->tableName)}'",
            "'$column'",
            "'{$this->generateTableName($refTable)}'",
            "'$refColumn'",
        ]);
    }
    
    /**
     * Returns foreign key name.
     * @param string $column
     * @return string
     */
    public function generateForeignKeyName($column)
    {
        return implode('-', [
            'fk',
            $this->tableName,
            $column
        ]);
    }
}
