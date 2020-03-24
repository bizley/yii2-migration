<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\db\Exception;

interface HistoryManagerInterface
{
    /**
     * @param string $migrationName
     * @param string|null $namespace
     * @throws Exception
     */
    public function addHistory(string $migrationName, string $namespace = null): void;

    /** @return array<string, string> */
    public function fetchHistory(): array;
}
