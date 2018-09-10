<?php

declare(strict_types=1);

namespace bizley\migration\tests\mysql;

use bizley\migration\Updater;
use Yii;
use yii\console\controllers\MigrateController;
use bizley\migration\tests\migrations\m180322_212600_create_table_test_pk;
use bizley\migration\tests\migrations\m180317_093600_create_table_test_columns;
use bizley\migration\tests\migrations\m180322_214400_create_table_test_index_single;
use bizley\migration\tests\migrations\m180322_213900_create_table_test_pk_composite;
use bizley\migration\tests\migrations\m180324_105400_create_table_test_fk;
use bizley\migration\tests\migrations\m180328_205600_create_table_test_multiple;
use bizley\migration\tests\migrations\m180328_205700_add_column_two_to_table_test_multiple;
use bizley\migration\tests\migrations\m180328_205900_drop_column_one_from_table_test_multiple;
use bizley\migration\tests\migrations\m180701_160300_create_table_test_int_size;
use bizley\migration\tests\migrations\m180701_160900_create_table_test_char_pk;

abstract class MysqlDbUpdaterTestCase extends MysqlDbTestCase
{
    static protected $runMigrations = false;

    /**
     * @param $name
     * @throws \yii\db\Exception
     */
    protected static function addMigration($name): void
    {
        Yii::$app->db->createCommand()->insert('migration', [
            'version' => $name,
            'apply_time' => time(),
        ])->execute();
    }

    /**
     * @param $name
     * @throws \yii\db\Exception
     */
    protected static function deleteMigration($name): void
    {
        Yii::$app->db->createCommand()->delete('migration', ['version' => $name])->execute();
    }

    protected function getUpdater($tableName, $generalSchema = true, array $skip = []): Updater
    {
        return new Updater([
            'db' => Yii::$app->db,
            'tableName' => $tableName,
            'generalSchema' => $generalSchema,
            'skipMigrations' => $skip,
        ]);
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws \yii\db\Exception
     */
    public static function setUpBeforeClass() // BC declaration
    {
        parent::setUpBeforeClass();
        if (!\in_array('migration', Yii::$app->db->schema->tableNames, true)) {
            Yii::$app->db->createCommand()->createTable('migration', [
                'version' => 'VARCHAR(180) NOT NULL PRIMARY KEY',
                'apply_time' => 'integer',
            ])->execute();
            static::addMigration(MigrateController::BASE_MIGRATION);
        }
    }

    protected function dbUp($name): void
    {
        $tableOptions = 'ENGINE=InnoDB';
        $data = [
            'test_pk' => function () use ($tableOptions) {
                if (!\in_array('test_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_pk', ['id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'], $tableOptions)->execute();
                    static::addMigration(m180322_212600_create_table_test_pk::class);
                }
            },
            'test_columns' => function () use ($tableOptions) {
                if (!\in_array('test_columns', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_columns', [
                        'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
                        'col_big_int' => 'BIGINT(20) NULL',
                        'col_int' => 'INT(11) NULL',
                        'col_small_int' => 'SMALLINT(6) NULL',
                        'col_bin' => 'BINARY NULL',
                        'col_bool' => 'TINYINT(1) NULL',
                        'col_char' => 'CHAR(1) NULL',
                        'col_date' => 'DATE NULL',
                        'col_date_time' => 'DATETIME NULL',
                        'col_decimal' => 'DECIMAL(10) NULL',
                        'col_double' => 'DOUBLE NULL',
                        'col_float' => 'FLOAT NULL',
                        'col_money' => 'DECIMAL(19,4) NULL',
                        'col_string' => 'VARCHAR(255) NULL',
                        'col_text' => 'TEXT NULL',
                        'col_time' => 'TIME NULL',
                        'col_timestamp' => 'TIMESTAMP NULL',
                    ], $tableOptions)->execute();
                    static::addMigration(m180317_093600_create_table_test_columns::class);
                }
            },
            'test_index_single' => function () use ($tableOptions) {
                if (!\in_array('test_index_single', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_index_single', ['col' => 'INT(11)'], $tableOptions)->execute();
                    Yii::$app->db->createCommand()->createIndex('idx-test_index_single-col', 'test_index_single', 'col')->execute();
                    static::addMigration(m180322_214400_create_table_test_index_single::class);
                }
            },
            'test_pk_composite' => function () use ($tableOptions) {
                if (!\in_array('test_pk_composite', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_pk_composite', [
                        'one' => 'INT(11)',
                        'two' => 'INT(11)',
                    ], $tableOptions)->execute();
                    Yii::$app->db->createCommand()->addPrimaryKey('PRIMARYKEY', 'test_pk_composite', ['one', 'two'])->execute();
                    static::addMigration(m180322_213900_create_table_test_pk_composite::class);
                }
            },
            'test_fk' => function () use ($tableOptions) {
                if (!\in_array('test_fk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_fk', ['pk_id' => 'INT(11)'], $tableOptions)->execute();
                    Yii::$app->db->createCommand()->addForeignKey('fk-test_fk-pk_id', '{{%test_fk}}', 'pk_id', '{{%test_pk}}', 'id', 'CASCADE', 'CASCADE')->execute();
                    static::addMigration(m180324_105400_create_table_test_fk::class);
                }
            },
            'test_multiple' => function () use ($tableOptions) {
                if (!\in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_multiple', ['two' => 'INT(11)'], $tableOptions)->execute();
                    static::addMigration(m180328_205600_create_table_test_multiple::class);
                    static::addMigration(m180328_205700_add_column_two_to_table_test_multiple::class);
                    static::addMigration(m180328_205900_drop_column_one_from_table_test_multiple::class);
                }
            },
            'test_multiple_skip' => function () use ($tableOptions) {
                if (!\in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_multiple', [
                        'one' => 'INT(11)',
                        'two' => 'INT(11)',
                    ], $tableOptions)->execute();
                    static::addMigration(m180328_205600_create_table_test_multiple::class);
                    static::addMigration(m180328_205700_add_column_two_to_table_test_multiple::class);
                    static::addMigration(m180328_205900_drop_column_one_from_table_test_multiple::class);
                }
            },
            'test_int_size' => function () use ($tableOptions) {
                if (!\in_array('test_int_size', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_int_size', [
                        'col_int' => 'INT(10) NULL',
                    ], $tableOptions)->execute();
                    static::addMigration(m180701_160300_create_table_test_int_size::class);
                }
            },
            'test_char_pk' => function () use ($tableOptions) {
                if (!\in_array('test_char_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable('test_char_pk', [
                        'id' => 'CHAR(128) NOT NULL PRIMARY KEY',
                    ], $tableOptions)->execute();
                    static::addMigration(m180701_160900_create_table_test_char_pk::class);
                }
            },
        ];
        \call_user_func($data[$name]);
    }

    protected function dbDown($name): void
    {
        // needs reverse order
        $data = [
            'test_char_pk' => function () {
                if (\in_array('test_char_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_char_pk')->execute();
                    static::deleteMigration(m180701_160900_create_table_test_char_pk::class);
                }
            },
            'test_int_size' => function () {
                if (\in_array('test_int_size', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_int_size')->execute();
                    static::deleteMigration(m180701_160300_create_table_test_int_size::class);
                }
            },
            'test_multiple' => function () {
                if (\in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_multiple')->execute();
                    static::deleteMigration(m180328_205900_drop_column_one_from_table_test_multiple::class);
                    static::deleteMigration(m180328_205700_add_column_two_to_table_test_multiple::class);
                    static::deleteMigration(m180328_205600_create_table_test_multiple::class);
                }
            },
            'test_fk' => function () {
                if (\in_array('test_fk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_fk')->execute();
                    static::deleteMigration(m180324_105400_create_table_test_fk::class);
                }
            },
            'test_pk_composite' => function () {
                if (\in_array('test_pk_composite', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_pk_composite')->execute();
                    static::deleteMigration(m180322_213900_create_table_test_pk_composite::class);
                }
            },
            'test_index_single' => function () {
                if (\in_array('test_index_single', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_index_single')->execute();
                    static::deleteMigration(m180322_214400_create_table_test_index_single::class);
                }
            },
            'test_columns' => function () {
                if (\in_array('test_columns', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_columns')->execute();
                    static::deleteMigration(m180317_093600_create_table_test_columns::class);
                }
            },
            'test_pk' => function () {
                if (\in_array('test_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_pk')->execute();
                    static::deleteMigration(m180322_212600_create_table_test_pk::class);
                }
            },
        ];
        if ($name === 'ALL') {
            foreach ($data as $tab) {
                $tab();
            }
        } else {
            \call_user_func($data[$name]);
        }
    }
}
