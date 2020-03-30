<?php

declare(strict_types=1);

namespace bizley\tests\unit\stubs;

use bizley\migration\controllers\MigrationController;
use RuntimeException;

class MigrationControllerStub extends MigrationController
{
    /** @var string */
    public static $stdout = '';

    public function stdout($string) // BC declaration
    {
        static::$stdout .= $string;
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
}
