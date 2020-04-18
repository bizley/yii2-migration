<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

interface BlueprintRendererInterface
{
    /**
     * Renders the blueprint for up().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#up()-detail
     * @param BlueprintInterface $blueprint
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderUp(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;

    /**
     * Renders the blueprint for down().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#down()-detail
     * @param BlueprintInterface $blueprint
     * @param int $indent
     * @param string|null $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderDown(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;
}
