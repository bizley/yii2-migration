<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

use function sprintf;

final class BlueprintRenderer implements BlueprintRendererInterface
{
    /** @var BlueprintInterface */
    private $blueprint;

    public function setBlueprint(BlueprintInterface $blueprint): void
    {
        $this->blueprint = $blueprint;
    }

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
