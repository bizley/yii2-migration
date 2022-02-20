<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use PDO;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

use function version_compare;

/** @group mysql */
final class UpdaterTest extends \bizley\tests\functional\UpdaterTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    private $v8;

    public function isV8(): bool
    {
        if ($this->v8 === null) {
            $this->v8 = version_compare(
                $this->getDb()->getSlavePdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
                '8.0.17',
                '>='
            );
        }

        return $this->v8;
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingColumn(): void
    {
        $this->getDb()->createCommand()->dropColumn('updater_base', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'col\');
    }

    public function safeDown()
    {
        $this->addColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumn(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->string())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->string());
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnSize(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer(8))->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            $this->isV8()
                ? ''
                : 'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer(8));
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnDefault(): void
    {
        $this->getDb()->createCommand()->alterColumn(
            'updater_base',
            'col',
            $this->integer()->defaultValue(4)
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer()->defaultValue(\'4\'));
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnAsFirst(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->first())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->first());
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnWithAfter(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->after('col'))->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->after(\'col\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnWithUnsigned(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->unsigned())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->unsigned()->after(\'col3\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnWithNotNull(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->notNull())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->notNull()->after(\'col3\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnWithComment(): void
    {
        $this->getDb()->createCommand()->addColumn(
            'updater_base',
            'added',
            $this->integer()->comment('test')
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->comment(\'test\')->after(\'col3\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnWithUnsigned(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->unsigned())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer()->unsigned());
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnWithNotNull(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->notNull())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer()->notNull());
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnWithComment(): void
    {
        $this->getDb()->createCommand()->alterColumn(
            'updater_base',
            'col',
            $this->integer()->comment('test')
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer()->comment(\'test\'));
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringColumnWithUnique(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->unique())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'col\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function safeDown()
    {
        $this->dropIndex(\'col\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingForeignKey(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');
    }

    public function safeDown()
    {
        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingForeignKey(): void
    {
        $this->getDb()->createCommand()->addForeignKey(
            'fk-added',
            'updater_base_fk',
            'col',
            'updater_base',
            'id'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            $this->isV8()
                ? 'public function safeUp()
    {
        $this->addForeignKey(
            \'fk-added\',
            \'{{%updater_base_fk}}\',
            [\'col\'],
            \'{{%updater_base}}\',
            [\'id\'],
            \'NO ACTION\',
            \'NO ACTION\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-added\', \'{{%updater_base_fk}}\');
    }'
                : 'public function safeUp()
    {
        $this->addForeignKey(
            \'fk-added\',
            \'{{%updater_base_fk}}\',
            [\'col\'],
            \'{{%updater_base}}\',
            [\'id\'],
            \'RESTRICT\',
            \'RESTRICT\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-added\', \'{{%updater_base_fk}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringForeignKeyColumns(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-plus',
            'updater_base_fk',
            'col',
            'updater_base_fk_target',
            'id'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            $this->isV8()
            ? 'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'col\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'NO ACTION\',
            \'NO ACTION\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }' : 'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'col\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'RESTRICT\',
            \'RESTRICT\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAlteringForeignKeyConstraints(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-plus',
            'updater_base_fk',
            'updater_base_id',
            'updater_base_fk_target',
            'id',
            'NO ACTION',
            'RESTRICT'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'NO ACTION\',
            \'RESTRICT\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingColumnWithFK(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->dropColumn('updater_base_fk', 'updater_base_id')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropIndex(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->dropColumn(\'{{%updater_base_fk}}\', \'updater_base_id\');
    }

    public function safeDown()
    {
        $this->addColumn(\'{{%updater_base_fk}}\', \'updater_base_id\', $this->integer());

        $this->createIndex(\'fk-plus\', \'{{%updater_base_fk}}\', [\'updater_base_id\']);

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'updater_base_id\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }',
            MigrationControllerStub::$content
        );
    }
}
