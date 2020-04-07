<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use Throwable;
use yii\base\NotSupportedException;
use yii\console\controllers\MigrateController;
use yii\db\ColumnSchemaBuilder;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\SchemaBuilderTrait;

use function array_reverse;
use function get_class;
use function time;

abstract class DbLoaderTestCase extends DbTestCase
{
    use SchemaBuilderTrait;

    /** @var string */
    public static $tableOptions;

    /** @var string */
    public $historyTable = '{{%migration}}';

    protected function getDb(): Connection
    {
        return static::$db;
    }

    /**
     * @param array $tables
     * @throws Exception
     * @throws NotSupportedException
     */
    protected function createTables(array $tables): void
    {
        $reverseOrderTables = array_reverse($tables);
        foreach ($reverseOrderTables as $table => $columns) {
            if ($this->getDb()->getSchema()->getTableSchema($table)) {
                $this->getDb()->createCommand()->dropTable($table)->execute();
            }
        }

        foreach ($tables as $table => $columns) {
            $this->getDb()->createCommand()->createTable($table, $columns, static::$tableOptions)->execute();
            foreach ($columns as $column => $type) {
                if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
                    $this->getDb()->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
                }
            }
        }
    }

    /**
     * @param array $migrations
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addUpdateBases(array $migrations): void
    {
        if ($this->getDb()->getSchema()->getTableSchema($this->historyTable) === null) {
            $this->createTable();
        }

        try {
            $reverseOrderMigrations = array_reverse($migrations);
            foreach ($reverseOrderMigrations as $migration) {
                $migration->compact = true;
                $migration->down();
                $this->getDb()
                    ->createCommand()
                    ->delete(
                        $this->historyTable,
                        ['version' => get_class($migration)]
                    )
                    ->execute();
            }
        } catch (Throwable $exception) {
        }

        foreach ($migrations as $migration) {
            $migration->compact = true;
            $migration->up();
            $this->getDb()
                ->createCommand()
                ->insert(
                    $this->historyTable,
                    [
                        'version' => get_class($migration),
                        'apply_time' => time(),
                    ]
                )
                ->execute();
        }
    }

    /**
     * @throws Exception
     */
    private function createTable(): void
    {
        $this->getDb()
            ->createCommand()
            ->createTable(
                $this->historyTable,
                [
                    'version' => 'varchar(' . MigrateController::MAX_NAME_LENGTH . ') NOT NULL PRIMARY KEY',
                    'apply_time' => 'integer',
                ]
            )
            ->execute();
        $this->getDb()
            ->createCommand()
            ->insert(
                $this->historyTable,
                [
                    'version' => MigrateController::BASE_MIGRATION,
                    'apply_time' => 1586131200,
                ]
            )
            ->execute();
    }
}
