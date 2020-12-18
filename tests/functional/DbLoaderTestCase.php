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
            $this->dropTable($table);
        }

        foreach ($tables as $table => $columns) {
            $this->createTable($table, $columns);
        }
    }

    /**
     * @param string $table
     * @throws Exception
     * @throws NotSupportedException
     */
    private function dropTable(string $table): void
    {
        if ($this->getDb()->getSchema()->getTableSchema($table)) {
            $this->getDb()->createCommand()->dropTable($table)->execute();
        }
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
     * @param string $schema
     * @throws Exception
     */
    protected function createSchema(string $schema): void
    {
        $this->getDb()->createCommand('CREATE SCHEMA IF NOT EXISTS ' . $schema)->execute();
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addBase(): void
    {
        $this->createMigrationHistoryTable();

        $this->dropTable('updater_base_fk_with_idx');
        $this->dropTable('updater_base_fk');
        $this->dropTable('updater_base_fk_target');
        $this->dropTable('updater_base');
        $this->dropTable('renamed_base');

        // Tables are added like this and not through the migration to skip class' autoloading.
        $this->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
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
        $this->getDb()->createCommand()->createIndex('idx-col', 'updater_base_fk', 'col')->execute();
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

        $columns = [
            'id' => $this->primaryKey(),
            'updater_base_id' => $this->integer(),
            'amount' => $this->decimal(10, 2)->notNull(),
            'dec_no_scale' => $this->decimal(20, 0)->notNull(),
        ];
        if (static::$schema === 'sqlite') {
            $columns[] = 'FOREIGN KEY(updater_base_id) REFERENCES updater_base_fk_target(id)';
        }
        $this->createTable('updater_base_fk_with_idx', $columns);
        $this->getDb()
            ->createCommand()
            ->createIndex('idx-updater_base_id', 'updater_base_fk_with_idx', 'updater_base_id')
            ->execute();
        if (static::$schema !== 'sqlite') {
            $this->getDb()->createCommand()->addForeignKey(
                'fk-existing-ids',
                'updater_base_fk_with_idx',
                'updater_base_id',
                'updater_base_fk_target',
                'id',
                'CASCADE',
                'CASCADE'
            )->execute();
        }

        $this->addHistoryEntry('m20200406_124200_create_table_updater_base');
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addPkBase(int $engineSpecificSize = null): void
    {
        $this->createMigrationHistoryTable();

        $this->dropTable('string_pk');
        $this->dropTable('no_pk');

        // Tables are added like this and not through the migration to skip class' autoloading.
        $this->createTable(
            'no_pk',
            [
                'col' => $this->integer(),
                'col2' => $this->integer()
            ]
        );

        $columns = ['col' => $this->string($engineSpecificSize)];
        if (static::$schema === 'sqlite') {
            $columns[] = 'PRIMARY KEY(col)';
        }
        $this->createTable('string_pk', $columns);
        if (static::$schema !== 'sqlite') {
            $this->getDb()->createCommand()->addPrimaryKey('string_pk-primary-key', 'string_pk', 'col')->execute();
        }

        $this->addHistoryEntry('m20200414_130200_create_table_pk_base');
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addSchemasBase(): void
    {
        $this->createMigrationHistoryTable();

        $this->dropTable('schema2.table1');
        $this->dropTable('schema1.table1');
        $this->dropTable('table1');

        $this->createSchema('schema1');
        $this->createSchema('schema2');

        // Tables are added like this and not through the migration to skip class' autoloading.
        $this->createTable(
            'table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ]
        );
        $this->createTable(
            'schema1.table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ]
        );
        $this->createTable(
            'schema2.table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ]
        );

        $this->addHistoryEntry('m20200422_210000_create_table_schemas_base');
    }

    /**
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function addExperimentalBase(): void
    {
        $this->createMigrationHistoryTable();

        $this->dropTable('exp_updater_base');

        $cols = [
            'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'col1' => 'VARCHAR(255) COMMENT \'test\'',
            'col2' => 'INTEGER(10) UNSIGNED',
            'col3' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'col4' => 'FLOAT',
            'col5' => 'DECIMAL(10, 3)',
            'col6' => 'ENUM(\'one\', \'two\')'
        ];
        if (static::$schema === 'pgsql') {
            $cols['id'] = 'serial NOT NULL PRIMARY KEY';
            $cols['col1'] = 'VARCHAR(255)';
            $cols['col2'] = 'INTEGER';
            $cols['col3'] = 'timestamp NOT NULL DEFAULT NOW()';
            unset($cols['col4'], $cols['col6']);
        }
        if (static::$schema === 'sqlite') {
            $cols['id'] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
            $cols['col1'] = 'VARCHAR(255)';
            $cols['col2'] = 'INTEGER(10)';
            unset($cols['col6']);
        }
        // Tables are added like this and not through the migration to skip class' autoloading.
        $this->createTable('exp_updater_base', $cols);

        $this->addHistoryEntry('m20200709_121500_create_table_exp_updater_base');
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    private function createMigrationHistoryTable(): void
    {
        if ($this->getDb()->getSchema()->getTableSchema($this->historyTable) !== null) {
            $this->getDb()->createCommand()->truncateTable($this->historyTable)->execute();
            $this->addHistoryEntry(MigrateController::BASE_MIGRATION);

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
        $this->addHistoryEntry(MigrateController::BASE_MIGRATION);
    }

    protected function addHistoryEntry(string $version): void
    {
        $this->getDb()
            ->createCommand()
            ->insert(
                $this->historyTable,
                [
                    'version' => $version,
                    'apply_time' => time(),
                ]
            )
            ->execute();
    }
}
