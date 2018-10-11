<?php declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;

/**
 * Class TablePlan
 * @package bizley\migration\table
 */
class TablePlan extends BaseObject
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
    public function render(TableStructure $table): string
    {
        $output = '';

        foreach ($this->dropColumn as $name) {
            $output .= "        \$this->dropColumn('" . $table->renderName() . "', '{$name}');\n";
        }

        /* @var $column TableColumn */
        foreach ($this->addColumn as $name => $column) {
            $output .= "        \$this->addColumn('" . $table->renderName() . "', '{$name}', " . $column->renderDefinition($table) . ");\n";
        }

        /* @var $column TableColumn */
        foreach ($this->alterColumn as $name => $column) {
            $output .= "        \$this->alterColumn('" . $table->renderName() . "', '{$name}', " . $column->renderDefinition($table) . ");\n";
        }

        foreach ($this->dropForeignKey as $name) {
            $output .= "        \$this->dropForeignKey('{$name}', '" . $table->renderName() . "');\n";
        }

        /* @var $foreignKey TableForeignKey */
        foreach ($this->addForeignKey as $name => $foreignKey) {
            $output .= $foreignKey->render($table);
        }

        foreach ($this->dropIndex as $name) {
            $output .= "        \$this->dropIndex('{$name}', '" . $table->renderName() . "');\n";
        }

        /* @var $index TableIndex */
        foreach ($this->createIndex as $name => $index) {
            $output .= $index->render($table) . "\n";
        }

        if (!empty($this->dropPrimaryKey)) {
            $output .= "        \$this->dropPrimaryKey('{$this->dropPrimaryKey}', '" . $table->renderName() . "');\n";
        }

        if ($this->addPrimaryKey) {
            $output .= $this->addPrimaryKey->render($table) . "\n";
        }

        return $output;
    }
}
