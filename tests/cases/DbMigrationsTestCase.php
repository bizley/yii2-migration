<?php

namespace bizley\tests\cases;

use bizley\migration\Updater;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\controllers\MigrateController;
use yii\console\Exception as ConsoleException;
use yii\db\Exception;
use yii\db\SchemaBuilderTrait;
use yii\helpers\Json;

abstract class DbMigrationsTestCase extends DbTestCase
{
    use SchemaBuilderTrait;

    static protected $runMigrations = false;

    /**
     * @var string
     */
    public static $tableOptions;

    /**
     * @param string $name
     * @throws Exception
     */
    protected static function addMigration($name)
    {
        Yii::$app->db->createCommand()->insert('migration', [
            'version' => $name,
            'apply_time' => time(),
        ])->execute();
    }

    /**
     * @param string $name
     * @throws Exception
     */
    protected static function deleteMigration($name)
    {
        Yii::$app->db->createCommand()->delete('migration', ['version' => $name])->execute();
    }

    /**
     * @param string $tableName
     * @param bool $generalSchema
     * @param array $skip
     * @return Updater
     */
    protected function getUpdater($tableName, $generalSchema = true, $skip = [])
    {
        return new Updater([
            'db' => Yii::$app->db,
            'tableName' => $tableName,
            'generalSchema' => $generalSchema,
            'skipMigrations' => $skip,
        ]);
    }

    /**
     * @throws InvalidRouteException
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        if (!in_array('migration', Yii::$app->db->schema->tableNames, true)) {
            Yii::$app->db->createCommand()->createTable('migration', [
                'version' => 'varchar(180) NOT NULL PRIMARY KEY',
                'apply_time' => 'integer',
            ])->execute();

            static::addMigration(MigrateController::BASE_MIGRATION);
        }
    }

    /**
     * @param string $name
     */
    protected function dbUp($name)
    {
        $data = [
            'test_pk' => function () {
                if (!in_array('test_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_pk',
                        ['id' => $this->primaryKey()],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180322_212600_create_table_test_pk');
                }
            },
            'test_columns' => function () {
                if (!in_array('test_columns', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_columns',
                        [
                            'id' => $this->primaryKey(),
                            'col_big_int' => $this->bigInteger(),
                            'col_int' => $this->integer(),
                            'col_small_int' => $this->smallInteger(),
                            'col_bin' => $this->binary(),
                            'col_bool' => $this->boolean(),
                            'col_char' => $this->char(),
                            'col_date' => $this->date(),
                            'col_date_time' => $this->dateTime(),
                            'col_decimal' => $this->decimal(),
                            'col_double' => $this->double(),
                            'col_float' => $this->float(),
                            'col_money' => $this->money(),
                            'col_string' => $this->string(),
                            'col_text' => $this->text(),
                            'col_time' => $this->time(),
                            'col_timestamp' => $this->timestamp(),
                        ],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180317_093600_create_table_test_columns');
                }
            },
            'test_index_single' => function () {
                if (!in_array('test_index_single', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_index_single',
                        ['col' => $this->integer()],
                        static::$tableOptions
                    )->execute();
                    Yii::$app->db->createCommand()->createIndex(
                        'idx-test_index_single-col',
                        'test_index_single',
                        'col'
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180322_214400_create_table_test_index_single');
                }
            },
            'test_pk_composite' => function () {
                if (!in_array('test_pk_composite', Yii::$app->db->schema->tableNames, true)) {
                    $columns = [
                        'one' => $this->integer(),
                        'two' => $this->integer(),
                    ];
                    if (Yii::$app->db->driverName === 'sqlite') {
                        $columns[] = 'PRIMARY KEY(one, two)';
                    }
                    Yii::$app->db->createCommand()->createTable(
                        'test_pk_composite',
                        $columns,
                        static::$tableOptions
                    )->execute();
                    if (Yii::$app->db->driverName !== 'sqlite') {
                        Yii::$app->db->createCommand()->addPrimaryKey(
                            'PRIMARYKEY',
                            'test_pk_composite',
                            ['one', 'two']
                        )->execute();
                    }

                    static::addMigration('bizley\\tests\\migrations\\m180322_213900_create_table_test_pk_composite');
                }
            },
            'test_fk' => function () {
                if (!in_array('test_fk', Yii::$app->db->schema->tableNames, true)) {
                    $columns = ['pk_id' => $this->integer()];
                    if (Yii::$app->db->driverName === 'sqlite') {
                        $columns[] = 'FOREIGN KEY(pk_id) REFERENCES test_pk(id)';
                    }

                    Yii::$app->db->createCommand()->createTable(
                        'test_fk',
                        $columns,
                        static::$tableOptions
                    )->execute();
                    if (Yii::$app->db->driverName !== 'sqlite') {
                        Yii::$app->db->createCommand()->addForeignKey(
                            'fk-test_fk-pk_id',
                            'test_fk',
                            'pk_id',
                            'test_pk',
                            'id',
                            'CASCADE',
                            'CASCADE'
                        )->execute();
                    }

                    static::addMigration('bizley\\tests\\migrations\\m180324_105400_create_table_test_fk');
                }
            },
            'test_multiple' => function () {
                if (!in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_multiple',
                        ['two' => $this->integer()],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180328_205600_create_table_test_multiple');
                    static::addMigration('bizley\\tests\\migrations\\m180328_205700_add_column_two_to_table_test_multiple');
                    static::addMigration('bizley\\tests\\migrations\\m180328_205900_drop_column_one_from_table_test_multiple');
                }
            },
            'test_multiple_skip' => function () {
                if (!in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_multiple',
                        [
                            'one' => $this->integer(),
                            'two' => $this->integer(),
                        ],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180328_205600_create_table_test_multiple');
                    static::addMigration('bizley\\tests\\migrations\\m180328_205700_add_column_two_to_table_test_multiple');
                    static::addMigration('bizley\\tests\\migrations\\m180328_205900_drop_column_one_from_table_test_multiple');
                }
            },
            'test_int_size' => function () {
                if (!in_array('test_int_size', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_int_size',
                        ['col_int' => $this->integer(10)],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180701_160300_create_table_test_int_size');
                }
            },
            'test_char_pk' => function () {
                if (!in_array('test_char_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_char_pk',
                        ['id' => $this->char(128)->notNull()->append('PRIMARY KEY')],
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180701_160900_create_table_test_char_pk');
                }
            },
            'test_addons' => function () {
                if (!in_array('test_addons', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->createTable(
                        'test_addons',
                        ['col_default_array' => $this->json()->defaultValue(Json::encode([1, 2, 3]))], // just this column needed for purpose of test
                        static::$tableOptions
                    )->execute();

                    static::addMigration('bizley\\tests\\migrations\\m180324_153800_create_table_test_addons');
                }
            },
        ];
        call_user_func($data[$name]);
    }

    /**
     * @param string $name
     */
    protected function dbDown($name)
    {
        // needs reverse order
        $data = [
            'test_addons' => function () {
                if (in_array('test_addons', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_addons')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180324_153800_create_table_test_addons');
                }
            },
            'test_char_pk' => function () {
                if (in_array('test_char_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_char_pk')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180701_160900_create_table_test_char_pk');
                }
            },
            'test_int_size' => function () {
                if (in_array('test_int_size', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_int_size')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180701_160300_create_table_test_int_size');
                }
            },
            'test_multiple' => function () {
                if (in_array('test_multiple', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_multiple')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180328_205900_drop_column_one_from_table_test_multiple');
                    static::deleteMigration('bizley\\tests\\migrations\\m180328_205700_add_column_two_to_table_test_multiple');
                    static::deleteMigration('bizley\\tests\\migrations\\m180328_205600_create_table_test_multiple');
                }
            },
            'test_fk' => function () {
                if (in_array('test_fk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_fk')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180324_105400_create_table_test_fk');
                }
            },
            'test_pk_composite' => function () {
                if (in_array('test_pk_composite', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_pk_composite')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180322_213900_create_table_test_pk_composite');
                }
            },
            'test_index_single' => function () {
                if (in_array('test_index_single', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_index_single')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180322_214400_create_table_test_index_single');
                }
            },
            'test_columns' => function () {
                if (in_array('test_columns', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_columns')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180317_093600_create_table_test_columns');
                }
            },
            'test_pk' => function () {
                if (in_array('test_pk', Yii::$app->db->schema->tableNames, true)) {
                    Yii::$app->db->createCommand()->dropTable('test_pk')->execute();

                    static::deleteMigration('bizley\\tests\\migrations\\m180322_212600_create_table_test_pk');
                }
            },
        ];
        if ($name === 'ALL') {
            foreach ($data as $tab) {
                $tab();
            }
        } else {
            call_user_func($data[$name]);
        }
    }
}
