<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\controllers\MigrationController;
use RuntimeException;

final class MigrationControllerStub extends MigrationController
{
    /** @var string */
    public static $stdout = '';

    /** @var bool */
    public static $confirmControl = true;

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
     * @param bool $throw
     */
    public function storeFile(string $path, $content, bool $throw = false): void
    {
        if ($throw) {
            throw new RuntimeException('Migration file can not be saved!');
        }
    }

    public function confirm($message, $default = false): bool // BC declaration
    {
        $this->stdout($message);

        return static::$confirmControl;
    }
}
