<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;

use function sprintf;

class Plan extends BaseObject
{
    /** @var array */
    public $dropColumn = [];

    /** @var array */
    public $addColumn = [];

    /** @var array */
    public $alterColumn = [];

    /** @var array */
    public $dropForeignKey = [];

    /** @var array */
    public $addForeignKey = [];

    /** @var string */
    public $dropPrimaryKey;

    /** @var PrimaryKey */
    public $addPrimaryKey;

    /** @var array */
    public $dropIndex = [];

    /** @var array */
    public $createIndex = [];

    /**
     * Renders migration changes.
     * @param Structure $table
     * @return string
     */
    public function render(Structure $table): string
    {
        $output = '';

        foreach ($this->dropColumn as $name) {
            $output .= sprintf('        $this->dropColumn(\'%s\', \'%s\');', $table->renderName(), $name) . "\n";
        }

        /* @var $column Column */
        foreach ($this->addColumn as $name => $column) {
            $output .= sprintf(
                '        $this->addColumn(\'%s\', \'%s\', %s);',
                $table->renderName(),
                $name,
                $column->renderDefinition($table)
            ) . "\n";
        }

        /* @var $column Column */
        foreach ($this->alterColumn as $name => $column) {
            $output .= sprintf(
                '        $this->alterColumn(\'%s\', \'%s\', %s);',
                $table->renderName(),
                $name,
                $column->renderDefinition($table)
            ) . "\n";
        }

        foreach ($this->dropForeignKey as $name) {
            $output .= sprintf('        $this->dropForeignKey(\'%s\', \'%s\');', $name, $table->renderName()) . "\n";
        }

        /* @var $foreignKey ForeignKey */
        foreach ($this->addForeignKey as $name => $foreignKey) {
            $output .= $foreignKey->render($table);
        }

        foreach ($this->dropIndex as $name) {
            $output .= sprintf('        $this->dropIndex(\'%s\', \'%s\');', $name, $table->renderName()) . "\n";
        }

        /* @var $index Index */
        foreach ($this->createIndex as $name => $index) {
            $output .= $index->render($table) . "\n";
        }

        if (!empty($this->dropPrimaryKey)) {
            $output .= sprintf(
                '        $this->dropPrimaryKey(\'%s\', \'%s\');',
                $this->dropPrimaryKey,
                $table->renderName()
            ) . "\n";
        }

        if ($this->addPrimaryKey) {
            $output .= $this->addPrimaryKey->render($table) . "\n";
        }

        return $output;
    }
}
