<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\controllers\MigrationController;

final class MigrationControllerStub extends MigrationController
{
    /** @var string */
    public static $stdout = '';

    /** @var bool */
    public static $confirmControl = true;

    /** @var string */
    public static $content;

    public function stdout($string) // BC declaration
    {
        static::$stdout .= $string;
    }

    public function ansiFormat($string): string // BC declaration
    {
        return $string;
    }

    /**
     * @param string $path
     * @param mixed $content
     */
    public function storeFile(string $path, $content): void
    {
        static::$content = $content;
    }

    public function confirm($message, $default = false): bool // BC declaration
    {
        $this->stdout($message);

        return static::$confirmControl;
    }
}
