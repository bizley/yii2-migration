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

class OldForeignKey extends BaseObject
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var string
     */
    public $referencedTable;

    /**
     * @var array
     */
    public $referencedColumns;

    /**
     * @var string
     */
    public $onDelete;

    /**
     * @var string
     */
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
        $tableName = $this->referencedTable;

        if (!$table->usePrefix) {
            return $tableName;
        }

        if ($table->dbPrefix && strpos($this->referencedTable, $table->dbPrefix) === 0) {
            $tableName = substr($this->referencedTable, mb_strlen($table->dbPrefix, 'UTF-8'));
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
            count($this->referencedColumns) === 1
                ? "'{$this->referencedColumns[0]}'"
                : "['" . implode("', '", $this->referencedColumns) . "']",
            $this->onDelete ? ",$innerIndent'{$this->onDelete}'" : '',
            $this->onUpdate
                ? ($this->onDelete === null ? ",{$innerIndent}null" : '') . ",$innerIndent'{$this->onUpdate}'"
                : ''
        );
    }
}
