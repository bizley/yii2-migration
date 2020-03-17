<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\db\Exception;

use function time;

final class HistoryManager implements HistoryManagerInterface
{
    /** @var Connection */
    private $db;

    /** @var string */
    private $table;

    public function __construct(Connection $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
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
                $this->table,
                [
                    'version' => 'varchar(' . MigrateController::MAX_NAME_LENGTH . ') NOT NULL PRIMARY KEY',
                    'apply_time' => 'integer',
                ]
            )
            ->execute();
        $this->db
            ->createCommand()
            ->insert(
                $this->table,
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
     */
    public function addHistory(string $migrationName, string $namespace = null): void
    {
        if ($this->db->schema->getTableSchema($this->table, true) === null) {
            $this->createTable();
        }

        $this->db
            ->createCommand()
            ->insert(
                $this->table,
                [
                    'version' => ($namespace ? $namespace . '\\' : '') . $migrationName,
                    'apply_time' => time(),
                ]
            )
            ->execute();
    }
}
