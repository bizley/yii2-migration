<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use yii\console\controllers\MigrateController;

final class MigrateControllerStub extends MigrateController
{
    /** @var string */
    public static $stdout = '';

    public function stdout($string) // BC declaration
    {
        static::$stdout .= $string;
    }
}
