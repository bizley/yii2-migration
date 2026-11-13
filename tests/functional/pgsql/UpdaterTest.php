<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

#[Group('pgsql')]
final class UpdaterTest extends \bizley\tests\functional\UpdaterTestCase
{
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAddingIndexWithMethod(): void
    {
        $this->getDb()->createCommand('CREATE INDEX "idx-add" ON "updater_base" USING hash ("col")')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'idx-add\', \'{{%updater_base}}\', [\'col\'], \'hash\');
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAddingIndexWithDefinition(): void
    {
        $this->getDb()->createCommand('CREATE INDEX "idx-add" ON "updater_base" (lower("col2"))')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->execute(\'CREATE INDEX [[idx-add]] ON {{%updater_base}} USING btree (lower((col2)::text))\');
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByChangingIndexDefinition(): void
    {
        $this->getDb()->createCommand()->dropIndex('idx-dec-partial', 'updater_base_fk_with_idx')->execute();
        $this->getDb()->createCommand(
            'CREATE INDEX "idx-dec-partial" ON "updater_base_fk_with_idx" ("dec_no_scale") WHERE "dec_no_scale" > 10'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk_with_idx']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropIndex(\'idx-dec-partial\', \'{{%updater_base_fk_with_idx}}\');

        $this->execute(\'CREATE INDEX [[idx-dec-partial]] ON {{%updater_base_fk_with_idx}} USING btree (dec_no_scale) '
            . 'WHERE (dec_no_scale > (10)::numeric)\');
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-dec-partial\', \'{{%updater_base_fk_with_idx}}\');

        $this->execute(\'CREATE INDEX [[idx-dec-partial]] ON {{%updater_base_fk_with_idx}} USING btree (dec_no_scale) '
            . 'WHERE (dec_no_scale > (0)::numeric)\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByChangingIndexMethod(): void
    {
        $this->getDb()->createCommand()->dropIndex('idx-amount-hash', 'updater_base_fk_with_idx')->execute();
        $this->getDb()
            ->createCommand()
            ->createIndex('idx-amount-hash', 'updater_base_fk_with_idx', 'amount')
            ->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk_with_idx']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropIndex(\'idx-amount-hash\', \'{{%updater_base_fk_with_idx}}\');

        $this->createIndex(\'idx-amount-hash\', \'{{%updater_base_fk_with_idx}}\', [\'amount\']);
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-amount-hash\', \'{{%updater_base_fk_with_idx}}\');

        $this->createIndex(\'idx-amount-hash\', \'{{%updater_base_fk_with_idx}}\', [\'amount\'], \'hash\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAlteringColumnSize(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col2', $this->string(45))->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col2\', $this->string(45));
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%updater_base}}\', \'col2\', $this->string());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAddingColumnWithDefaultExpression(): void
    {
        $this->getDb()->createCommand()->addColumn(
            'updater_base',
            'added',
            $this->string()->defaultExpression('md5(random()::text)')
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->string()->defaultExpression(\'md5((random())::text)\')->after(\'col3\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAddingBooleanColumnWithDefault(): void
    {
        $this->getDb()->createCommand()->addColumn(
            'updater_base',
            'added',
            $this->boolean()->defaultValue(false)
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->boolean()->defaultValue(false)->after(\'col3\'));
    }

    public function safeDown()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAlteringColumnWithUnique(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->unique())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'updater_base_col_key\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function safeDown()
    {
        $this->dropIndex(\'updater_base_col_key\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
            'public function safeUp()
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
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAlteringForeignKey(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-plus',
            'updater_base_fk',
            'col',
            'updater_base_fk_target',
            'id',
            'CASCADE',
            'CASCADE'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->addForeignKey(
            \'fk-plus\',
            \'{{%updater_base_fk}}\',
            [\'col\'],
            \'{{%updater_base_fk_target}}\',
            [\'id\'],
            \'CASCADE\',
            \'CASCADE\'
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
            'public function safeUp()
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
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByDroppingColumnWithFK(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->dropColumn('updater_base_fk', 'updater_base_id')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropForeignKey(\'fk-plus\', \'{{%updater_base_fk}}\');

        $this->dropColumn(\'{{%updater_base_fk}}\', \'updater_base_id\');
    }

    public function safeDown()
    {
        $this->addColumn(\'{{%updater_base_fk}}\', \'updater_base_id\', $this->integer());

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
