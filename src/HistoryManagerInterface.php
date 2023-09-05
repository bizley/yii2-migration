<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\base\NotSupportedException;
use yii\db\Exception;

interface HistoryManagerInterface
{
    /**
     * Adds migration history entry.
     * @throws Exception
     * @throws NotSupportedException
     */
    public function addHistory(string $migrationName, string $namespace = null): void;

    /**
     * Returns the migration history.
     * @return array<string, string> the migration history
     * @throws NotSupportedException
     */
    public function fetchHistory(): array;
}
