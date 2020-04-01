<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

interface ForeignKeyRendererInterface
{
    /**
     * Renders the add foreign key statement.
     * @param ForeignKeyInterface $foreignKey
     * @param string $tableName
     * @param string $referencedTableName
     * @param int $indent
     * @return string
     */
    public function renderUp(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0
    ): string;

    /**
     * Renders the drop foreign key statement.
     * @param ForeignKeyInterface $foreignKey
     * @param string $tableName
     * @param int $indent
     * @return string
     */
    public function renderDown(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        int $indent = 0
    ): string;
}
