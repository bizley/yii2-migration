<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;

interface ColumnRendererInterface
{
    /**
     * Renders the array part with column definition (name => definition).
     */
    public function render(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    /**
     * Renders the column definition.
     */
    public function renderDefinition(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    /**
     * Renders the add column statement.
     */
    public function renderAdd(
        ColumnInterface $column,
        string $tableName,
        PrimaryKeyInterface $primaryKey = null,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    /**
     * Renders the alter column statement.
     */
    public function renderAlter(
        ColumnInterface $column,
        string $tableName,
        PrimaryKeyInterface $primaryKey = null,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    /**
     * Renders the drop column statement.
     */
    public function renderDrop(ColumnInterface $column, string $tableName, int $indent = 0): ?string;
}
