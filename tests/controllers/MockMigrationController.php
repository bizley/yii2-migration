<?php

declare(strict_types=1);

namespace bizley\tests\controllers;

use bizley\migration\controllers\MigrationController;

class MockMigrationController extends MigrationController
{
    /**
     * @var string output buffer.
     */
    private $stdOutBuffer = '';

    /**
     * @param string $string
     */
    public function stdout($string): void // BC declaration
    {
        $this->stdOutBuffer .= $string;
    }

    /**
     * @return string
     */
    public function flushStdOutBuffer(): string
    {
        $result = $this->stdOutBuffer;
        $this->stdOutBuffer = '';

        return $result;
    }
}
