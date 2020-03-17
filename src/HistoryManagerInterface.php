<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\db\Exception;

interface HistoryManagerInterface
{
    /**
     * @param string $version
     * @param string|null $namespace
     * @throws Exception
     */
    public function addHistory(string $version, string $namespace = null): void;
}
