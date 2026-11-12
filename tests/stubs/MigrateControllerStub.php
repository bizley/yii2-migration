<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use yii\console\controllers\MigrateController;

final class MigrateControllerStub extends MigrateController
{
    /** @var string */
    public static $stdout = '';

    /**
     * @param string $string
     * @return int
     */
    public function stdout($string) // BC declaration
    {
        self::$stdout .= $string;

        return \strlen($string);
    }
}
