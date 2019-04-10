<?php

declare(strict_types=1);

namespace bizley\tests\pgsql;

use bizley\tests\cases\UpdaterColumnsTestCase;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception as BaseException;
use yii\base\NotSupportedException;
use yii\db\Exception;
use yii\db\JsonExpression;
use yii\helpers\Json;

/**
 * @group pgsql
 */
class UpdaterColumnsTest extends UpdaterColumnsTestCase
{
    public static $schema = 'pgsql';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testChangeSizeSpecific(): void
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_char', $this->char(2))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_char', $updater->plan->alterColumn);
        $this->assertEquals(2, $updater->plan->alterColumn['col_char']->length);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     * @throws BaseException
     */
    public function testChangeDefaultArrayValue(): void
    {
        $this->dbUp('test_addons');

        Yii::$app->db->createCommand()->alterColumn(
            'test_addons',
            'col_default_array',
            $this->json()->defaultValue(new JsonExpression(['a', 'b']))
        )->execute();

        $updater = $this->getUpdater('test_addons', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_default_array', $updater->plan->alterColumn);
        $this->assertEquals(['a', 'b'], $updater->plan->alterColumn['col_default_array']->default);
    }
}
