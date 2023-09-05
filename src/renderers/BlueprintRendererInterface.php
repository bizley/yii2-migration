<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

interface BlueprintRendererInterface
{
    /**
     * Renders the blueprint for up().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#up()-detail
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
