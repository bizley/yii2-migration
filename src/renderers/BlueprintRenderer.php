<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;

use function array_filter;
use function implode;

final class BlueprintRenderer implements BlueprintRendererInterface
{
    /** @var ColumnRendererInterface */
    private $columnRenderer;

    /** @var PrimaryKeyRendererInterface */
    private $primaryKeyRenderer;

    /** @var IndexRendererInterface */
    private $indexRenderer;

    /** @var ForeignKeyRendererInterface */
    private $foreignKeyRenderer;

    public function __construct(
        ColumnRendererInterface $columnRenderer,
        PrimaryKeyRendererInterface $primaryKeyRenderer,
        IndexRendererInterface $indexRenderer,
        ForeignKeyRendererInterface $foreignKeyRenderer
    ) {
        $this->columnRenderer = $columnRenderer;
        $this->primaryKeyRenderer = $primaryKeyRenderer;
        $this->indexRenderer = $indexRenderer;
        $this->foreignKeyRenderer = $foreignKeyRenderer;
    }

    /**
     * Renders the blueprint for up().
     * @param BlueprintInterface $blueprint
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderUp(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string {
        $renderedBlueprint = array_filter(
            [
                $this->renderColumnsToDrop($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderColumnsToAdd($blueprint, $indent, $schema, $engineVersion, $usePrefix, $dbPrefix),
                $this->renderColumnsToAlter($blueprint, $indent, $schema, $engineVersion, $usePrefix, $dbPrefix),
                $this->renderForeignKeysToDrop($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderForeignKeysToAdd($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderIndexesToDrop($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderIndexesToAdd($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderPrimaryKeyToDrop($blueprint, $indent, $usePrefix, $dbPrefix),
                $this->renderPrimaryKeyToAdd($blueprint, $indent, $usePrefix, $dbPrefix),
            ]
        );

        return implode("\n\n", $renderedBlueprint);
    }

    /**
     * Renders table name.
     * @param string|null $tableName
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderName(?string $tableName, bool $usePrefix, string $dbPrefix = null): ?string
    {
        if ($usePrefix === false) {
            return $tableName;
        }

        if ($dbPrefix !== null && strpos($tableName, $dbPrefix) === 0) {
            $tableName = mb_substr($tableName, mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    private function renderColumnsToDrop(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedColumns = [];

        $columns = $blueprint->getDroppedColumns();
        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderDrop(
                $column,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedColumns) ? implode("\n", $renderedColumns) : null;
    }

    private function renderColumnsToAdd(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedColumns = [];

        $columns = $blueprint->getAddedColumns();
        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderAdd(
                $column,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                null, // TODO should there be primary key?
                $indent,
                $schema,
                $engineVersion
            );
        }

        return count($renderedColumns) ? implode("\n", $renderedColumns) : null;
    }

    private function renderColumnsToAlter(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedColumns = [];

        $columns = $blueprint->getAlteredColumns();
        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderAlter(
                $column,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                null, // TODO should there be primary key?
                $indent,
                $schema,
                $engineVersion
            );
        }

        return count($renderedColumns) ? implode("\n", $renderedColumns) : null;
    }

    private function renderForeignKeysToDrop(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedForeignKeys = [];

        $foreignKeys = $blueprint->getDroppedForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderDown(
                $foreignKey,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    private function renderForeignKeysToAdd(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedForeignKeys = [];

        $foreignKeys = $blueprint->getAddedForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderUp(
                $foreignKey,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                $this->renderName($foreignKey->getReferencedTable(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    private function renderIndexesToDrop(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedIndexes = [];

        $indexes = $blueprint->getDroppedIndexes();
        /** @var IndexInterface $index */
        foreach ($indexes as $index) {
            $renderedIndexes[] = $this->indexRenderer->renderDown(
                $index,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    private function renderIndexesToAdd(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedIndexes = [];

        $indexes = $blueprint->getAddedIndexes();
        /** @var IndexInterface $index */
        foreach ($indexes as $index) {
            $renderedIndexes[] = $this->indexRenderer->renderUp(
                $index,
                $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    private function renderPrimaryKeyToDrop(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->primaryKeyRenderer->renderDown(
            $blueprint->getDroppedPrimaryKey(),
            $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
            $indent
        );
    }

    private function renderPrimaryKeyToAdd(
        BlueprintInterface $blueprint,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->primaryKeyRenderer->renderUp(
            $blueprint->getDroppedPrimaryKey(),
            $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix),
            $indent
        );
    }
}
