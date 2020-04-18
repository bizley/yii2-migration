<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

interface IndexRendererInterface
{
    /**
     * Renders the add index statement.
     * @param IndexInterface $index
     * @param string $tableName
     * @param int $indent
     * @return string|null
     */
    public function renderUp(IndexInterface $index, string $tableName, int $indent = 0): ?string;

    /**
     * Renders the drop index statement.
     * @param IndexInterface $index
     * @param string $tableName
     * @param int $indent
     * @return string|null
     */
    public function renderDown(IndexInterface $index, string $tableName, int $indent = 0): ?string;
}
