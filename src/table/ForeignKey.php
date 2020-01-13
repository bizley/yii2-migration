<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;

use function count;
use function implode;
use function is_numeric;
use function mb_strlen;
use function sprintf;
use function str_repeat;
use function strpos;
use function substr;

class ForeignKey extends BaseObject
{
    /** @var string */
    public $name;

    /** @var array */
    public $columns;

    /** @var string */
    public $refTable;

    /** @var array */
    public $refColumns;

    /** @var string */
    public $onDelete;

    /** @var string */
    public $onUpdate;

    /**
     * Renders key name.
     * @param Structure $table
     * @return string
     */
    public function renderName(Structure $table): string
    {
        if ($this->name === null || is_numeric($this->name)) {
            return sprintf('fk-%s-%s', $table->name, implode('-', $this->columns));
        }

        return $this->name;
    }

    /**
     * Renders reference table name.
     * @param Structure $table
     * @return string
     */
    public function renderRefTableName(Structure $table): string
    {
        $tableName = $this->refTable;

        if (!$table->usePrefix) {
            return $tableName;
        }

        if ($table->dbPrefix && strpos($this->refTable, $table->dbPrefix) === 0) {
            $tableName = substr($this->refTable, mb_strlen($table->dbPrefix, 'UTF-8'));
        }

        return '{{%' . $tableName . '}}';
    }

    /**
     * Renders the key.
     * @param Structure $table
     * @param int $indent
     * @return string
     */
    public function render(Structure $table, int $indent = 8): string
    {
        $innerIndent = "\n" . str_repeat(' ', $indent + 4) . "\n";
        return str_repeat(' ', $indent) . sprintf(
            '$this->addForeignKey(%s\'%s\',%s\'%s\',%s%s,%s\'%s\',%s%s%s%s);',
            $innerIndent,
            $this->renderName($table),
            $innerIndent,
            $table->renderName(),
            $innerIndent,
            count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']",
            $innerIndent,
            $this->renderRefTableName($table),
            $innerIndent,
            count($this->refColumns) === 1
                ? "'{$this->refColumns[0]}'"
                : "['" . implode("', '", $this->refColumns) . "']",
            $this->onDelete ? ",$innerIndent'{$this->onDelete}'" : '',
            $this->onUpdate
                ? ($this->onDelete === null ? ",{$innerIndent}null" : '') . ",$innerIndent'{$this->onUpdate}'"
                : ''
        );
    }
}
