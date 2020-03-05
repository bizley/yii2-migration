<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;

use function count;
use function implode;
use function is_numeric;
use function mb_strlen;
use function sprintf;
use function strpos;
use function substr;

class ForeignKeyData extends BaseObject
{
    /** @var ForeignKey */
    public $foreignKey;

    /** @var Structure */
    public $table;

    /**
     * Renders key name.
     * @return string
     */
    public function renderName(): string
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
    public function renderRefTableName(): string
    {
        $tableName = $this->foreignKey->referencedTable;

        if (!$this->table->usePrefix) {
            return $tableName;
        }

        if ($this->table->dbPrefix && strpos($this->foreignKey->referencedTable, $this->table->dbPrefix) === 0) {
            $tableName = substr($this->foreignKey->referencedTable, mb_strlen($this->table->dbPrefix, 'UTF-8'));
        }

        return '{{%' . $tableName . '}}';
    }

    /**
     * Renders the key.
     * @return string
     */
    public function render(): string
    {
        $indent = "\n" . str_repeat(' ', 12) . "\n";
        return str_repeat(' ', 8) . sprintf(
            '$this->addForeignKey(%s\'%s\',%s\'%s\',%s%s,%s\'%s\',%s%s%s%s);',
            $indent,
            $this->renderName(),
            $indent,
            $this->table->renderName(),
            $indent,
            count($this->foreignKey->columns) === 1
                ? "'{$this->foreignKey->columns[0]}'"
                : "['" . implode("', '", $this->foreignKey->columns) . "']",
            $indent,
            $this->renderRefTableName(),
            $indent,
            count($this->foreignKey->referencedColumns) === 1
                ? "'{$this->foreignKey->referencedColumns[0]}'"
                : "['" . implode("', '", $this->foreignKey->referencedColumns) . "']",
            $this->foreignKey->onDelete ? ",$indent'{$this->foreignKey->onDelete}'" : '',
            $this->foreignKey->onUpdate
                ? (
                    $this->foreignKey->onDelete === null
                        ? ",{$indent}null"
                        : ''
                ) . ",$indent'{$this->foreignKey->onUpdate}'"
                : ''
        );
    }
}
