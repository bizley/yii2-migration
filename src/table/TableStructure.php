<?php

namespace bizley\migration\table;

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
     * Returns schema type.
     * @return string
     */
    public function getSchema()
    {
        return $this->_schema;
    }

    protected $_schema;

    /**
     * Sets schema type based on the currently used schema class.
     * @param string $schemaClass
     */
    public function setSchema($schemaClass)
    {
        switch ($schemaClass) {
            case 'yii\db\mssql\Schema':
                $this->_schema = self::SCHEMA_MSSQL;
                break;
            case 'yii\db\oci\Schema':
                $this->_schema = self::SCHEMA_OCI;
                break;
            case 'yii\db\pgsql\Schema':
                $this->_schema = self::SCHEMA_PGSQL;
                break;
            case 'yii\db\sqlite\Schema':
                $this->_schema = self::SCHEMA_SQLITE;
                break;
            case 'yii\db\cubrid\Schema':
                $this->_schema = self::SCHEMA_CUBRID;
                break;
            case 'yii\db\mysql\Schema':
                $this->_schema = self::SCHEMA_MYSQL;
                break;
            default:
                $this->_schema = self::SCHEMA_UNSUPPORTED;
        }
    }

    public function renderName()
    {
        if (!$this->usePrefix) {
            return $this->name;
        }
        return '{{%' . $this->name . '}}';
    }

    public function render()
    {
        return $this->renderTable() . $this->renderPk() . $this->renderIndexes() . $this->renderForeignKeys();
    }

    public function renderTable()
    {
        $output = '';

        $tableOptionsSet = false;
        if ($this->generalSchema || $this->schema === self::SCHEMA_MYSQL) {
            $output .= <<<'PHP'
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }


PHP;
            $tableOptionsSet = true;
        }
        $output .= "        \$this->createTable('" . $this->renderName() . "', [\n";
        foreach ($this->columns as $column) {
            $output .= $column->render($this);
        }
        $output .= '        ]' . ($tableOptionsSet ? ', $tableOptions' : '') . ");\n";

        return $output;
    }

    public function renderPk()
    {
        $output = '';
        if ($this->primaryKey->isComposite()) {
            $output .= $this->primaryKey->render($this);
        }
        return $output;
    }

    public function renderIndexes()
    {
        $output = '';
        if ($this->indexes) {
            $output .= "\n";
            foreach ($this->indexes as $index) {
                $output .= $index->render($this);
            }
        }
        return $output;
    }

    public function renderForeignKeys()
    {
        $output = '';
        if ($this->foreignKeys) {
            $output .= "\n";
            foreach ($this->foreignKeys as $foreignKey) {
                $output .= $foreignKey->render($this);
            }
        }
        return $output;
    }
}
