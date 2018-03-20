<?php

namespace bizley\migration\tests;

use bizley\migration\Generator;
use Yii;

class GeneratorTest extends TestCase
{
    protected function getGenerator($tableName, $generalSchema = true, $useTablePrefix = true, $namespace = null)
    {
        return new Generator([
            'db' => Yii::$app->db,
            'view' => Yii::$app->view,
            'templateFile' => '@bizley/migration/views/create_migration.php',
            'className' => 'm990101_000000_create_table_' . $tableName,
            'tableName' => $tableName,
            'useTablePrefix' => $useTablePrefix,
            'namespace' => $namespace,
            'generalSchema' => $generalSchema,
        ]);
    }

    public function testGenerateColumns_generalSchema()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

use yii\db\Migration;

class m990101_000000_create_table_test_columns extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_columns}}', [
            'id' => $this->primaryKey()->notNull(),
            'col_big_int' => $this->bigInteger(),
            'col_int' => $this->integer(),
            'col_small_int' => $this->smallInteger(),
            'col_bin' => $this->binary(),
            'col_bool' => $this->tinyInteger(),
            'col_char' => $this->char(),
            'col_date' => $this->date(),
            'col_date_time' => $this->dateTime(),
            'col_decimal' => $this->decimal(),
            'col_double' => $this->double(),
            'col_float' => $this->float(),
            'col_money' => $this->decimal(),
            'col_string' => $this->string(),
            'col_text' => $this->text(),
            'col_time' => $this->time(),
            'col_timestamp' => $this->timestamp(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_columns}}');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_columns')->generateMigration()));
    }

    public function testGenerateColumns_schemaSpecific()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

use yii\db\Migration;

class m990101_000000_create_table_test_columns extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_columns}}', [
            'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'col_big_int' => $this->bigInteger(20),
            'col_int' => $this->integer(11),
            'col_small_int' => $this->smallInteger(6),
            'col_bin' => $this->binary(),
            'col_bool' => $this->tinyInteger(1),
            'col_char' => $this->char(1),
            'col_date' => $this->date(),
            'col_date_time' => $this->dateTime(),
            'col_decimal' => $this->decimal(10),
            'col_double' => $this->double(),
            'col_float' => $this->float(),
            'col_money' => $this->decimal(19, 4),
            'col_string' => $this->string(255),
            'col_text' => $this->text(),
            'col_time' => $this->time(),
            'col_timestamp' => $this->timestamp(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_columns}}');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_columns', false)->generateMigration()));
    }

    public function testGenerateBasic_noPrefix()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

use yii\db\Migration;

class m990101_000000_create_table_test_basic extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('test_basic', [
            'id' => $this->primaryKey()->notNull(),
            'col_int' => $this->integer(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('test_basic');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_basic', true, false)->generateMigration()));
    }

    public function testGenerateBasic_namespace()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

namespace bizley\migration\tests;

use yii\db\Migration;

class m990101_000000_create_table_test_basic extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_basic}}', [
            'id' => $this->primaryKey()->notNull(),
            'col_int' => $this->integer(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_basic}}');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_basic', true, true, 'bizley\migration\tests')->generateMigration()));
    }

    public function testGenerateIndexes()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

use yii\db\Migration;

class m990101_000000_create_table_test_indexes extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_indexes}}', [
            'id' => $this->primaryKey()->notNull(),
            'col1' => $this->integer(),
            'col2' => $this->integer(),
            'col3' => $this->integer(),
            'col4' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('standard', '{{%test_indexes}}', 'col1');
        $this->createIndex('unique', '{{%test_indexes}}', 'col2', true);
        $this->createIndex('combined', '{{%test_indexes}}', ['col3', 'col4']);
    }

    public function down()
    {
        $this->dropTable('{{%test_indexes}}');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_indexes')->generateMigration()));
    }

    public function testGenerateForeignKey()
    {
        $this->assertEquals(str_replace("\r", '', <<<'PHP'
<?php

use yii\db\Migration;

class m990101_000000_create_table_test_foreign_key extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_foreign_key}}', [
            'id' => $this->primaryKey()->notNull(),
            'basic_id' => $this->integer(),
        ], $tableOptions);


        $this->addForeignKey('fk-test_foreign_key-test_basic', '{{%test_foreign_key}}', 'basic_id', '{{%test_basic}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('{{%test_foreign_key}}');
    }
}

PHP
        ), str_replace("\r", '', $this->getGenerator('test_foreign_key')->generateMigration()));
    }
}
