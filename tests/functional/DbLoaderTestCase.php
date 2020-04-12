<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use yii\base\NotSupportedException;
use yii\console\controllers\MigrateController;
use yii\db\ColumnSchemaBuilder;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\SchemaBuilderTrait;

use function array_reverse;

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
            $this->dropTable($table);
        }

        foreach ($tables as $table => $columns) {
            $this->createTable($table, $columns);
        }
    }

    /**
     * @param string $table
     * @return bool
     * @throws Exception
     * @throws NotSupportedException
     */
    private function dropTable(string $table): bool
    {
        if ($this->getDb()->getSchema()->getTableSchema($table)) {
            $this->getDb()->createCommand()->dropTable($table)->execute();
            return true;
        }
        return false;
    }

    /**
     * @param string $table
     * @param array $columns
     * @throws Exception
     */
    private function createTable(string $table, array $columns): void
    {
        $this->getDb()->createCommand()->createTable($table, $columns, static::$tableOptions)->execute();
        foreach ($columns as $column => $type) {
            if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
                $this->getDb()->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
            }
        }
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addBase(): void
    {
        $this->createMigrationHistoryTable();

        $this->dropTable('updater_base_fk');
        $this->dropTable('updater_base_fk_target');
        $this->dropTable('updater_base');

        $this->getDb()
            ->createCommand()
            ->delete($this->historyTable, ['version' => 'm20200406_124200_create_table_updater_base'])
            ->execute();

        // Tables are added like this and not through the migration to skip class' autoloading.
        $this->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ]
        );

        $this->createTable('updater_base_fk_target', ['id' => $this->primaryKey()]);

        $columns = [
            'id' => $this->primaryKey(),
            'col' => $this->integer(),
            'col2' => $this->integer()->unique(),
            'updater_base_id' => $this->integer(),
        ];
        if (static::$schema === 'sqlite') {
            $columns[] = 'FOREIGN KEY(updater_base_id) REFERENCES updater_base_fk_target(id)';
        }
        $this->createTable('updater_base_fk', $columns);
        $this->getDb()->createCommand()->createIndex('idx-updater_base_plus', 'updater_base_fk', 'col')->execute();
        if (static::$schema !== 'sqlite') {
            $this->getDb()->createCommand()->addForeignKey(
                'fk-plus',
                'updater_base_fk',
                'updater_base_id',
                'updater_base_fk_target',
                'id',
                'CASCADE',
                'CASCADE'
            )->execute();
        }

        $this->getDb()
            ->createCommand()
            ->insert(
                $this->historyTable,
                [
                    'version' => 'm20200406_124200_create_table_updater_base',
                    'apply_time' => 1586131201,
                ]
            )
            ->execute();
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    private function createMigrationHistoryTable(): void
    {
        if ($this->getDb()->getSchema()->getTableSchema($this->historyTable) !== null) {
            return;
        }
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
