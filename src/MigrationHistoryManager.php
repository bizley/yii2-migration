<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\db\Exception;

use function time;

final class MigrationHistoryManager implements MigrationHistoryManagerInterface
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
     * @param string $version
     * @param string|null $namespace
     * @throws Exception
     */
    public function addHistory(string $version, string $namespace = null): void
    {
        if ($this->db->schema->getTableSchema($this->table, true) === null) {
            $this->createTable();
        }

        $this->db
            ->createCommand()
            ->insert(
                $this->table,
                [
                    'version' => ($namespace ? $namespace . '\\' : '') . $version,
                    'apply_time' => time(),
                ]
            )
            ->execute();
    }
}
