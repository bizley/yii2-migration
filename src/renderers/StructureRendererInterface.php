<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;

interface StructureRendererInterface
{
    /**
     * Renders the migration structure for up().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#up()-detail
     * @param StructureInterface $structure
     * @param int $indent
     * @param string $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderStructureUp(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    /**
     * Renders the migration structure for down().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#down()-detail
     * @param StructureInterface $structure
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderStructureDown(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    /**
     * Renders table name. Name should be provided without the prefix. If name should be with prefix and it is being
     * detected, prefix is removed from the name and replaced by a prefix structure ({{%}}).
     * @param string $tableName
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderName(string $tableName, bool $usePrefix, string $dbPrefix = null): string;

    /**
     * Renders the add foreign keys statements (direct).
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @param string|null $schema
     * @return string|null
     */
    public function renderForeignKeysUp(
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null,
        string $schema = null
    ): ?string;

    /**
     * Renders the drop foreign keys statements.
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeysDown(
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string;
}
