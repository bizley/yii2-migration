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
use yii\db\Schema;
use yii\db\TableSchema;

/**
 * Description of Generator
 * @author Bizley
 * 
 * @property array $tableSchema
 */
class Generator extends Component
{
    /**
     * @var Connection
     */
    public $db;
    
    /**
     * @var string
     */
    public $tableName;
    
    /**
     * @var string
     */
    public $className;
    
    /**
     * @var View
     */
    public $view;
    
    /**
     * @var boolean indicates whether the table names generated should consider
     * the `tablePrefix` setting of the DB connection. For example, if the table
     * name is `post` the generator wil return `{{%post}}`.
     */
    public $useTablePrefix;
    
    /**
     * @var string
     */
    public $templateFile;
    
    protected $_tableSchema;
    
    public function init()
    {
        parent::init();
        if (!($this->db instanceof Connection)) {
            throw new InvalidConfigException('Parameter db must be an instance of yii\db\Connection!');
        }
    }

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
        die;
    }
    
    public function checkSchema()
    {
        if (!$this->tableSchema) {
            throw new InvalidParamException('Cannot find schema for ' . $this->tableName . ' table!');
        }
    }
    
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
    
    public function prepareForeignKeysDefinitions()
    {
        $keys = [];
        if ($this->tableSchema instanceof TableSchema) {
            foreach ($this->tableSchema->foreignKeys as $key) {
                $keys[] = $this->renderKeyDefinition($key);
            }
        }
        var_dump($keys);die;
        return $keys;
    }
    
    public function renderSize(ColumnSchema $column)
    {
        return $column->size ?: null;
    }
    
    public function renderScale(ColumnSchema $column)
    {
        return $column->scale ?: null;
    }
    
    public function renderPrecision(ColumnSchema $column)
    {
        return $column->precision ?: null;
    }
    
    public function renderColumnDefinition(ColumnSchema $column)
    {
        $definition = '';
        if ($column->isPrimaryKey) {
            if ($column->type == Schema::TYPE_BIGINT) {
                $definition .= '->bigPrimaryKey(' . $this->renderSize($column) . ')';
            } else {
                $definition .= '->primaryKey(' . $this->renderSize($column) . ')';
            }
        } else {
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
                $definition .= '->defaultValue(' . $column->defaultValue . ')';
            }
            if ($column->comment) {
                $definition .= '->comment(' . $column->comment . ')';
            }
        }
        
        return $definition;
    }
    
    public function renderKeyDefinition($key)
    {
        $refTable = $key[0];
        
        return $definition;
    }
}