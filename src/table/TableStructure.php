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
        $tableName = $this->name;
        if (!$this->usePrefix) {
            return $tableName;
        }
        if ($this->dbPrefix && strpos($this->name, $this->dbPrefix) === 0) {
            $tableName = substr($this->name, mb_strlen($this->dbPrefix, 'UTF-8'));
        }
        return '{{%' . $tableName . '}}';
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

    /**
     * @param TableChange[] $changes
     * @throws \yii\base\InvalidParamException
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
                    //todo
                    foreach ($change->value as $column) {
                        $this->_oldTable['columns'][$column] = $properties;
                        if (!empty($this->_oldTable['columns'][$column]['append']) && $this->findPrimaryKeyString($this->_oldTable['columns'][$column]['append'])) {
                            $this->_oldTable['pk'][] = $column;
                        }
                    }
                    break;
                case 'addColumn':
                    //todo
                    foreach (current($change) as $column => $properties) {
                        $this->_oldTable['columns'][$column] = $properties;
                        if (!empty($this->_oldTable['columns'][$column]['append']) && $this->findPrimaryKeyString($this->_oldTable['columns'][$column]['append'])) {
                            $this->_oldTable['pk'][] = $column;
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
                    //todo
                    if (isset($this->_oldTable['columns'][key(current($change))])) {
                        $this->_oldTable['columns'][key(current($change))] = current(current($change));
                    }
                    break;
                case 'addPrimaryKey':
                    //todo
                    $pk = current($change);
                    $this->_oldTable['pk'] = $pk;
                    foreach ($pk as $key) {
                        if (isset($this->_oldTable['columns'][$key])) {
                            if (empty($this->_oldTable['columns'][$key]['append'])) {
                                $this->_oldTable['columns'][$key]['append'] = $this->prepareSchemaAppend(true, false);
                            } elseif (!$this->findPrimaryKeyString($this->_oldTable['columns'][$key]['append'])) {
                                $this->_oldTable['columns'][$key]['append'] .= ' ' . $this->prepareSchemaAppend(true, false);
                            }
                        }
                    }
                    break;
                case 'dropPrimaryKey':
                    //todo
                    if (!empty($this->_oldTable['pk'])) {
                        foreach ($this->_oldTable['pk'] as $key) {
                            if (isset($this->_oldTable['columns'][$key]) && !empty($this->_oldTable['columns'][$key]['append'])) {
                                $append = $this->removePrimaryKeyString($this->_oldTable['columns'][$key]['append']);
                                if ($append) {
                                    $this->_oldTable['columns'][$key]['append'] = !is_string($append) || $append === ' ' ? null : $append;
                                }
                            }
                        }
                    }
                    $this->_oldTable['pk'] = [];
                    break;
                case 'addForeignKey':
                    //todo
                    $this->_oldTable['fks'][current($change)[0]] = [current($change)[1], current($change)[2], current($change)[3], current($change)[4], current($change)[5]];
                    break;
                case 'dropForeignKey':
                    //todo
                    if (isset($this->_oldTable['fks'][current($change)])) {
                        unset($this->_oldTable['fks'][current($change)]);
                    }
                    break;
                case 'createIndex':
                    //todo
                    $this->_oldTable['uidxs'][key(current($change))] = current(current($change));
                    break;
                case 'dropIndex':
                    //todo
                    if (isset($this->_oldTable['uidxs'][current($change)])) {
                        unset($this->_oldTable['uidxs'][current($change)]);
                    }
                    break;
                case 'addCommentOnColumn':
                    //todo
                    if (isset($this->_oldTable['columns'][key(current($change))])) {
                        $this->_oldTable['columns'][key(current($change))]['comment'] = current(current($change));
                    }
                    break;
                case 'dropCommentFromColumn':
                    //todo
                    if (isset($this->_oldTable['columns'][current($change)])) {
                        $this->_oldTable['columns'][current($change)]['comment'] = null;
                    }
            }
        }
    }
}
