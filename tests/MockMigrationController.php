<?php

declare(strict_types=1);

namespace bizley\migration\tests;

use bizley\migration\controllers\MigrationController;

/**
 * Class MockMigrationController
 * @package bizley\migration\tests
 */
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
