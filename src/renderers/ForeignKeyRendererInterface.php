<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

interface ForeignKeyRendererInterface
{
    /**
     * Renders the add foreign key statement.
     */
    public function renderUp(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0,
        string $schema = null
    ): string;

    /**
     * Renders the drop foreign key statement.
     */
    public function renderDown(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        int $indent = 0,
        string $schema = null
    ): string;
}
