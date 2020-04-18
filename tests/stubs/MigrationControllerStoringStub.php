<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\controllers\MigrationController;

final class MigrationControllerStoringStub extends MigrationController
{
    /** @var string */
    public static $stdout = '';

    /** @var bool */
    public static $confirmControl = true;

    /** @var string */
    public static $content = '';

    public function stdout($string) // BC declaration
    {
        static::$stdout .= $string;
    }

    public function ansiFormat($string): string // BC declaration
    {
        return $string;
    }

    public function confirm($message, $default = false): bool // BC declaration
    {
        $this->stdout($message);

        return static::$confirmControl;
    }
}
