<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

final class BlueprintRenderer implements BlueprintRendererInterface
{
    public function __construct(
        private readonly ColumnRendererInterface $columnRenderer,
        private readonly PrimaryKeyRendererInterface $primaryKeyRenderer,
        private readonly IndexRendererInterface $indexRenderer,
        private readonly ForeignKeyRendererInterface $foreignKeyRenderer
    ) {
    }

    /**
     * Renders the blueprint for up().
     * Foreign keys are dropped first and added last because they may depend on indexes, primary key, and columns.
     * Primary key is added after the columns, so it can include the new or altered ones.
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#up()-detail
     */
    public function renderUp(
        BlueprintInterface $blueprint,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null,
        bool $usePrefix = true,
        ?string $dbPrefix = null
    ): string {
        $tableName = $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix);

        $renderedBlueprint = \array_filter(
            [
                $this->renderForeignKeysToDrop($blueprint, $tableName, $indent, $schema),
                $this->renderIndexesToDrop($blueprint, $tableName, $indent),
                $this->renderPrimaryKeyToDrop($blueprint, $tableName, $indent, $schema),
                $this->renderColumnsToDrop($blueprint, $tableName, $indent),
                $this->renderColumnsToAdd($blueprint, $tableName, $indent, $schema, $engineVersion),
                $this->renderColumnsToAlter($blueprint, $tableName, $indent, $schema, $engineVersion),
                $this->renderPrimaryKeyToAdd($blueprint, $tableName, $indent, $schema),
                $this->renderIndexesToAdd($blueprint, $tableName, $indent),
                $this->renderForeignKeysToAdd($blueprint, $tableName, $indent, $schema, $usePrefix, $dbPrefix),
            ]
        );

        return \implode("\n\n", $renderedBlueprint);
    }

    /**
     * Renders the blueprint for down().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#down()-detail
     */
    public function renderDown(
        BlueprintInterface $blueprint,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null,
        bool $usePrefix = true,
        ?string $dbPrefix = null
    ): string {
        $tableName = $this->renderName($blueprint->getTableName(), $usePrefix, $dbPrefix);

        $renderedBlueprint = \array_filter(
            [
                $this->renderForeignKeysToDrop($blueprint, $tableName, $indent, $schema, true),
                $this->renderIndexesToDrop($blueprint, $tableName, $indent, true),
                $this->renderPrimaryKeyToDrop($blueprint, $tableName, $indent, $schema, true),
                $this->renderColumnsToDrop($blueprint, $tableName, $indent, true),
                $this->renderColumnsToAdd($blueprint, $tableName, $indent, $schema, $engineVersion, true),
                $this->renderColumnsToAlter($blueprint, $tableName, $indent, $schema, $engineVersion, true),
                $this->renderPrimaryKeyToAdd($blueprint, $tableName, $indent, $schema, true),
                $this->renderIndexesToAdd($blueprint, $tableName, $indent, true),
                $this->renderForeignKeysToAdd($blueprint, $tableName, $indent, $schema, $usePrefix, $dbPrefix, true),
            ]
        );

        return \implode("\n\n", $renderedBlueprint);
    }

    /**
     * Renders table name. Name should be provided without the prefix. If name should be with prefix, and it is being
     * detected, prefix is removed from the name and replaced by a prefix structure ({{%}}).
     * @param bool $usePrefix whether to add prefix structure to the name
     */
    public function renderName(string $tableName, bool $usePrefix, ?string $dbPrefix = null): string
    {
        if ($usePrefix === false) {
            return $tableName;
        }

        if (!empty($dbPrefix) && \str_starts_with($tableName, $dbPrefix)) {
            $tableName = \mb_substr($tableName, \mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    /**
     * Renders drop columns statements.
     */
    private function renderColumnsToDrop(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        bool $inverse = false
    ): ?string {
        $renderedColumns = [];

        if ($inverse) {
            $columns = $blueprint->getAddedColumns();
        } else {
            $columns = $blueprint->getDroppedColumns();
        }

        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderDrop($column, $tableName, $indent);
        }

        return !empty($renderedColumns) ? \implode("\n", $renderedColumns) : null;
    }

    /**
     * Renders add columns statements.
     */
    private function renderColumnsToAdd(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null,
        bool $inverse = false
    ): ?string {
        $renderedColumns = [];

        if ($inverse) {
            $columns = $blueprint->getDroppedColumns();
            $primaryKey = $blueprint->getTableOldPrimaryKey();
        } else {
            $columns = $blueprint->getAddedColumns();
            $primaryKey = $blueprint->getTableNewPrimaryKey();
        }

        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderAdd(
                $column,
                $tableName,
                $primaryKey,
                $indent,
                $schema,
                $engineVersion
            );
        }

        return !empty($renderedColumns) ? \implode("\n", $renderedColumns) : null;
    }

    /**
     * Renders alter columns statements.
     */
    private function renderColumnsToAlter(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null,
        bool $inverse = false
    ): ?string {
        $renderedColumns = [];

        if ($inverse) {
            $columns = $blueprint->getUnalteredColumns();
            $primaryKey = $blueprint->getTableOldPrimaryKey();
        } else {
            $columns = $blueprint->getAlteredColumns();
            $primaryKey = $blueprint->getTableNewPrimaryKey();
        }

        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->renderAlter(
                $column,
                $tableName,
                $primaryKey,
                $indent,
                $schema,
                $engineVersion
            );
        }

        return !empty($renderedColumns) ? \implode("\n", $renderedColumns) : null;
    }

    /**
     * Renders drop foreign keys statements.
     */
    private function renderForeignKeysToDrop(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        bool $inverse = false
    ): ?string {
        $renderedForeignKeys = [];

        if ($inverse) {
            $foreignKeys = $blueprint->getAddedForeignKeys();
        } else {
            $foreignKeys = $blueprint->getDroppedForeignKeys();
        }

        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderDown($foreignKey, $tableName, $indent, $schema);
        }

        return !empty($renderedForeignKeys) ? \implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * Renders add foreign keys statements.
     */
    private function renderForeignKeysToAdd(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        bool $usePrefix = true,
        ?string $dbPrefix = null,
        bool $inverse = false
    ): ?string {
        $renderedForeignKeys = [];

        if ($inverse) {
            $foreignKeys = $blueprint->getDroppedForeignKeys();
        } else {
            $foreignKeys = $blueprint->getAddedForeignKeys();
        }

        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderUp(
                $foreignKey,
                $tableName,
                $this->renderName($foreignKey->getReferredTable(), $usePrefix, $dbPrefix),
                $indent,
                $schema
            );
        }

        return !empty($renderedForeignKeys) ? \implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * Renders drop indexes statements.
     */
    private function renderIndexesToDrop(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        bool $inverse = false
    ): ?string {
        $renderedIndexes = [];

        if ($inverse) {
            $indexes = $blueprint->getAddedIndexes();
        } else {
            $indexes = $blueprint->getDroppedIndexes();
        }

        foreach ($indexes as $index) {
            $renderedIndexes[] = $this->indexRenderer->renderDown($index, $tableName, $indent);
        }

        return !empty($renderedIndexes) ? \implode("\n", $renderedIndexes) : null;
    }

    /**
     * Renders add indexes statements.
     */
    private function renderIndexesToAdd(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        bool $inverse = false
    ): ?string {
        $renderedIndexes = [];

        if ($inverse) {
            $indexes = $blueprint->getDroppedIndexes();
        } else {
            $indexes = $blueprint->getAddedIndexes();
        }

        foreach ($indexes as $index) {
            $renderedIndexes[] = $this->indexRenderer->renderUp($index, $tableName, $indent);
        }

        return !empty($renderedIndexes) ? \implode("\n", $renderedIndexes) : null;
    }

    /**
     * Renders drop primary key statement.
     */
    private function renderPrimaryKeyToDrop(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        bool $inverse = false
    ): ?string {
        if ($inverse) {
            $primaryKey = $blueprint->getAddedPrimaryKey();
        } else {
            $primaryKey = $blueprint->getDroppedPrimaryKey();
        }

        return $this->primaryKeyRenderer->renderDown($primaryKey, $tableName, $indent, $schema);
    }

    /**
     * Renders add primary key statement.
     */
    private function renderPrimaryKeyToAdd(
        BlueprintInterface $blueprint,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        bool $inverse = false
    ): ?string {
        if ($inverse) {
            $primaryKey = $blueprint->getDroppedPrimaryKey();
        } else {
            $primaryKey = $blueprint->getAddedPrimaryKey();
        }

        return $this->primaryKeyRenderer->renderUp($primaryKey, $tableName, $indent, $schema);
    }
}
