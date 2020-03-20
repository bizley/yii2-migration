<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\StructureInterface;

interface StructureRendererInterface
{
    public function renderStructure(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    public function renderName(?string $tableName, bool $usePrefix, string $dbPrefix = null): ?string;

    public function renderForeignKeys(
        StructureInterface $structure,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string;
}
