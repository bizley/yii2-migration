<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use yii\db\Connection;
use yii\db\SchemaBuilderTrait;

abstract class DbLoaderTestCase extends DbTestCase
{
    use SchemaBuilderTrait;

    protected function getDb(): Connection
    {
        return static::$db;
    }
}
