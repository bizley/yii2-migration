<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use yii\db\Connection;
use yii\db\Exception;
use yii\db\SchemaBuilderTrait;

abstract class DbLoaderTestCase extends DbTestCase
{
    use SchemaBuilderTrait;

    /** @var string */
    private $table;

    /** @var string */
    public static $tableOptions;

    protected function getDb(): Connection
    {
        return static::$db;
    }

    protected function setUp(): void
    {
        $this->table = null;
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $this->dropTable();
    }

    /**
     * @param string $table
     * @param array $columns
     * @throws Exception
     */
    protected function createTable(string $table, array $columns): void
    {
        $this->getDb()->createCommand()->createTable($table, $columns, static::$tableOptions)->execute();
        $this->table = $table;
    }

    /**
     * @throws Exception
     */
    private function dropTable(): void
    {
        if ($this->table !== null) {
            $this->getDb()->createCommand()->dropTable($this->table)->execute();
        }
    }
}
