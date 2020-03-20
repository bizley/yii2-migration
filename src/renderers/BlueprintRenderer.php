<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;

use function array_filter;
use function implode;
use function sprintf;
use function str_replace;

final class BlueprintRenderer implements BlueprintRendererInterface
{
    /** @var bool */
    private $usePrefix = true;

    /** @var string|null */
    private $dbPrefix;

    /** @var string */
    private $dropColumnTemplate = '$this->dropColumn(\'{tableName}\', \'{columnName}\');';

    /** @var string */
    private $addColumnTemplate = '$this->addColumn(\'{tableName}\', \'{columnName}\', {columnDefinition})';

    /** @var BlueprintInterface */
    private $blueprint;

    /** @var ColumnRendererInterface */
    private $columnRenderer;

    public function __construct(ColumnRendererInterface $columnRenderer)
    {
        $this->columnRenderer = $columnRenderer;
    }

    /**
     * Renders the blueprint for up().
     * @param string $schema
     * @param string|null $engineVersion
     * @param int $indent
     * @return string
     */
    public function renderUp(string $schema, string $engineVersion = null, int $indent = 0): string
    {
        $renderedBlueprint = array_filter(
            [
                $this->renderColumnsToDrop($indent),
                $this->renderColumnsToAdd($schema, $engineVersion, $indent),
                $this->renderColumnsToAlter($indent),
                $this->renderForeignKeysToDrop($indent),
                $this->renderForeignKeysToAdd($indent),
                $this->renderIndexesToDrop($indent),
                $this->renderIndexesToAdd($indent),
                $this->renderPrimaryKeyToDrop($indent),
                $this->renderPrimaryKeyToAdd($indent),
            ]
        );

        return implode("\n\n", $renderedBlueprint);
    }

    /**
     * Renders table name.
     * @param string|null $tableName
     * @return string|null
     */
    public function renderName(?string $tableName): ?string
    {
        if ($this->usePrefix === false) {
            return $tableName;
        }

        $dbPrefix = $this->dbPrefix;
        if ($dbPrefix !== null && strpos($tableName, $dbPrefix) === 0) {
            $tableName = mb_substr($tableName, mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    private function renderColumnsToDrop(int $indent = 0): ?string
    {
        $renderedColumns = [];

        $template = str_repeat(' ', $indent) . $this->dropColumnTemplate;

        $columns = $this->blueprint->getDroppedColumns();
        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $renderedColumns[] = str_replace(
                [
                    '{tableName}',
                    '{columnName}'
                ],
                [
                    $this->renderName($this->blueprint->getTableName()),
                    $column->getName()
                ],
                $template
            );
        }

        return count($renderedColumns) ? implode("\n", $renderedColumns) : null;
    }

    private function renderColumnsToAdd(string $schema, string $engineVersion = null, int $indent = 0): ?string
    {
        $renderedColumns = [];

        $template = str_repeat(' ', $indent) . $this->addColumnTemplate;

        $columns = $this->blueprint->getAddedColumns();
        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $renderedColumns[] = str_replace(
                [
                    '{tableName}',
                    '{columnName}',
                    '{columnDefinition}'
                ],
                [
                    $this->renderName($this->blueprint->getTableName()),
                    $column->getName(),
                    $this->columnRenderer->renderDefinition($schema, $engineVersion)
                ],
                $template
            );
        }

        return count($renderedColumns) ? implode("\n", $renderedColumns) : null;
    }













    public function render(int $indent = 0): string
    {
        $output = '';

//        foreach ($this->dropColumn as $name) {
//            $output .= sprintf('        $this->dropColumn(\'%s\', \'%s\');', $table->renderName(), $name) . "\n";
//        }

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

    public function setBlueprint(BlueprintInterface $blueprint): void
    {
        $this->blueprint = $blueprint;
    }

    /**
     * @param bool $usePrefix
     */
    public function setUsePrefix(bool $usePrefix): void
    {
        $this->usePrefix = $usePrefix;
    }

    /**
     * @param string|null $dbPrefix
     */
    public function setDbPrefix(?string $dbPrefix): void
    {
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * @param string $dropColumnTemplate
     */
    public function setDropColumnTemplate(string $dropColumnTemplate): void
    {
        $this->dropColumnTemplate = $dropColumnTemplate;
    }

    /**
     * @param string $addColumnTemplate
     */
    public function setAddColumnTemplate(string $addColumnTemplate): void
    {
        $this->addColumnTemplate = $addColumnTemplate;
    }
}
