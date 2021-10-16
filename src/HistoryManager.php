<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\base\NotSupportedException;
use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\ArrayHelper;

use function preg_match;
use function str_replace;
use function strcasecmp;
use function time;
use function usort;

final class HistoryManager implements HistoryManagerInterface
{
    /** @var Connection */
    private $db;

    /** @var string */
    private $historyTable;

    /** @var Query */
    private $query;

    public function __construct(Connection $db, Query $query, string $historyTable)
    {
        $this->db = $db;
        $this->query = $query;
        $this->historyTable = $historyTable;
    }

    /**
     * Creates the migration history table.
     * @throws Exception
     */
    private function createTable(): void
    {
        $this->db
            ->createCommand()
            ->createTable(
                $this->historyTable,
                [
                    'version' => 'varchar(' . MigrateController::MAX_NAME_LENGTH . ') NOT NULL PRIMARY KEY',
                    'apply_time' => 'integer',
                ]
            )
            ->execute();
        $this->db
            ->createCommand()
            ->insert(
                $this->historyTable,
                [
                    'version' => MigrateController::BASE_MIGRATION,
                    'apply_time' => time(),
                ]
            )
            ->execute();
    }

    /**
     * Adds migration history entry.
     * @param string $migrationName
     * @param string|null $namespace
     * @throws Exception
     * @throws NotSupportedException
     */
    public function addHistory(string $migrationName, string $namespace = null): void
    {
        if ($this->db->getSchema()->getTableSchema($this->historyTable, true) === null) {
            $this->createTable();
        }

        $this->db
            ->createCommand()
            ->insert(
                $this->historyTable,
                [
                    'version' => ($namespace ? $namespace . '\\' : '') . $migrationName,
                    'apply_time' => time(),
                ]
            )
            ->execute();
    }

    /**
     * Returns the migration history.
     * This is slightly modified Yii's MigrateController::getMigrationHistory() method.
     * Migrations are fetched from newest to oldest.
     * @return array<string, string> the migration history
     * @throws NotSupportedException
     */
    public function fetchHistory(): array
    {
        if ($this->db->getSchema()->getTableSchema($this->historyTable, true) === null) {
            return [];
        }

        $rows = $this->query
            ->select(['version', 'apply_time'])
            ->from($this->historyTable)
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC])
            ->all($this->db);

        $history = [];
        foreach ($rows as $row) {
            if ($row['version'] === MigrateController::BASE_MIGRATION) {
                continue;
            }

            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?/i', $row['version'], $matches)) {
                $row['canonicalVersion'] = str_replace('_', '', $matches[1]);
            } else {
                $row['canonicalVersion'] = $row['version'];
            }

            $row['apply_time'] = (int)$row['apply_time'];

            $history[] = $row;
        }

        usort(
            $history,
            static function ($a, $b) {
                if ($a['apply_time'] === $b['apply_time']) {
                    if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                        return $compareResult;
                    }

                    return strcasecmp($b['version'], $a['version']);
                }

                return $b['apply_time'] <=> $a['apply_time'];
            }
        );

        return ArrayHelper::map($history, 'version', 'apply_time');
    }
}
