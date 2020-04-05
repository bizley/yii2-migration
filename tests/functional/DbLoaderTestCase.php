<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use yii\base\NotSupportedException;
use yii\db\ColumnSchemaBuilder;
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
     * @param array $tables
     * @throws Exception
     * @throws NotSupportedException
     */
    protected function createTables(array $tables): void
    {
        $reverseOrderTables = array_reverse($tables);
        foreach ($reverseOrderTables as $table => $columns) {
            if ($this->getDb()->getSchema()->getTableSchema($table)) {
                $this->getDb()->createCommand()->dropTable($table)->execute();
            }
        }

        foreach ($tables as $table => $columns) {
            $this->getDb()->createCommand()->createTable($table, $columns, static::$tableOptions)->execute();
            foreach ($columns as $column => $type) {
                if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
                    $this->getDb()->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
                }
            }
        }
    }
}
