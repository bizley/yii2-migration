<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;

interface ColumnRendererInterface
{
    /**
     * Renders the array part with column definition (name => definition).
     * @param ColumnInterface $column
     * @param PrimaryKeyInterface|null $primaryKey
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
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
     * @param ColumnInterface $column
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
     */
    public function renderDefinition(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    /**
     * Renders the add column statement.
     * @param ColumnInterface $column
     * @param string $tableName
     * @param PrimaryKeyInterface|null $primaryKey
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
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
     * @param ColumnInterface $column
     * @param string $tableName
     * @param PrimaryKeyInterface|null $primaryKey
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
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
     * @param ColumnInterface $column
     * @param string $tableName
     * @param int $indent
     * @return string|null
     */
    public function renderDrop(ColumnInterface $column, string $tableName, int $indent = 0): ?string;
}
