<?php

namespace bizley\migration\table;

use yii\base\BaseObject;

/**
 * Class ForeignKeyData
 * @package bizley\migration\table
 * @since 2.7.0
 */
class ForeignKeyData extends BaseObject
{
    /**
     * @var TableForeignKey
     */
    public $foreignKey;

    /**
     * @var TableStructure
     */
    public $table;

    /**
     * Renders key name.
     * @return string
     */
    public function renderName()
    {
        if ($this->foreignKey->name === null || is_numeric($this->foreignKey->name)) {
            return sprintf('fk-%s-%s', $this->table->name, implode('-', $this->foreignKey->columns));
        }

        return $this->foreignKey->name;
    }

    /**
     * Renders reference table name.
     * @return string
     */
    public function renderRefTableName()
    {
        $tableName = $this->foreignKey->refTable;

        if (!$this->table->usePrefix) {
            return $tableName;
        }

        if ($this->table->dbPrefix && strpos($this->foreignKey->refTable, $this->table->dbPrefix) === 0) {
            $tableName = substr($this->foreignKey->refTable, mb_strlen($this->table->dbPrefix, 'UTF-8'));
        }

        return '{{%' . $tableName . '}}';
    }

    /**
     * Renders the key.
     * @return string
     */
    public function render()
    {
        return str_repeat(' ', 8) . sprintf(
            '$this->addForeignKey(\'%s\', \'%s\', %s, \'%s\', %s%s%s);',
            $this->renderName(),
            $this->table->renderName(),
            count($this->foreignKey->columns) === 1
                ? "'{$this->foreignKey->columns[0]}'"
                : "['" . implode("', '", $this->foreignKey->columns) . "']",
            $this->renderRefTableName(),
            count($this->foreignKey->refColumns) === 1
                ? "'{$this->foreignKey->refColumns[0]}'"
                : "['" . implode("', '", $this->foreignKey->refColumns) . "']",
            $this->foreignKey->onDelete ? ", '{$this->foreignKey->onDelete}'" : '',
            $this->foreignKey->onUpdate
                ? ($this->foreignKey->onDelete === null ? ', null' : '') . ", '{$this->foreignKey->onUpdate}'"
                : ''
        );
    }
}
