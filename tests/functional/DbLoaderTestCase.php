<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\SchemaBuilderTrait;

abstract class DbLoaderTestCase extends DbTestCase
{
    use SchemaBuilderTrait;

    /** @var string */
    public static $tableOptions;

    protected function getDb(): Connection
    {
        return static::$db;
    }

    /**
     * @param string $table
     * @param array $columns
     * @throws Exception
     * @throws NotSupportedException
     */
    protected function createTable(string $table, array $columns): void
    {
        if ($this->getDb()->getSchema()->getTableSchema($table)) {
            $this->getDb()->createCommand()->dropTable($table)->execute();
        }
        $this->getDb()->createCommand()->createTable($table, $columns, static::$tableOptions)->execute();
    }
}
