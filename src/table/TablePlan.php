<?php

namespace bizley\migration\table;

use yii\base\Object;

/**
 * Class TablePlan
 * @package bizley\migration\table
 */
class TablePlan extends Object
{
    /**
     * @var array
     */
    public $dropColumn = [];

    /**
     * @var array
     */
    public $addColumn = [];

    /**
     * @var array
     */
    public $alterColumn = [];

    /**
     * @var array
     */
    public $dropForeignKey = [];

    /**
     * @var array
     */
    public $addForeignKey = [];

    /**
     * @var string
     */
    public $dropPrimaryKey;

    /**
     * @var TablePrimaryKey
     */
    public $addPrimaryKey;

    /**
     * @var array
     */
    public $dropIndex = [];

    /**
     * @var array
     */
    public $createIndex = [];

    /**
     * Renders migration changes.
     * @param TableStructure $table
     * @return string
     */
    public function render($table)
    {
        $output = '';

        foreach ($this->dropColumn as $name) {
            $output .= sprintf('        $this->dropColumn(\'%s\', \'%s\');', $table->renderName(), $name) . "\n";
        }

        /* @var $column TableColumn */
        foreach ($this->addColumn as $name => $column) {
            $output .= sprintf(
                '        $this->addColumn(\'%s\', \'%s\', %s);',
                $table->renderName(),
                $name,
                $column->renderDefinition($table)
            ) . "\n";
        }

        /* @var $column TableColumn */
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

        /* @var $foreignKey TableForeignKey */
        foreach ($this->addForeignKey as $name => $foreignKey) {
            $output .= $foreignKey->render($table);
        }

        foreach ($this->dropIndex as $name) {
            $output .= sprintf('        $this->dropIndex(\'%s\', \'%s\');', $name, $table->renderName()) . "\n";
        }

        /* @var $index TableIndex */
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
