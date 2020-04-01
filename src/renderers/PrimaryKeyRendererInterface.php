<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

interface PrimaryKeyRendererInterface
{
    /**
     * Renders the add primary key statement.
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string $tableName
     * @param int $indent
     * @return string|null
     */
    public function renderUp(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string;

    /**
     * Renders the drop primary key statement.
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string $tableName
     * @param int $indent
     * @return string|null
     */
    public function renderDown(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string;
}
