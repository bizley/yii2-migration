<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;

interface StructureRendererInterface
{
    public function renderStructureUp(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    public function renderStructureDown(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    public function renderName(?string $tableName, bool $usePrefix, string $dbPrefix = null): ?string;

    /**
     * @param string $structureName
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeysUp(
        string $structureName,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string;

    /**
     * @param string $structureName
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeysDown(
        string $structureName,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string;
}
