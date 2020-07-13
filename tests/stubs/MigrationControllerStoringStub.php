<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\controllers\MigrationController;
use yii\console\Request;
use yii\console\Response;

final class MigrationControllerStoringStub extends MigrationController
{
    /** @var string */
    public static $stdout = '';

    /** @var bool */
    public static $confirmControl = true;

    /** @var string */
    public static $content = '';

    /** @var Request|array|string */
    public $request = Request::class;

    /** @var Response|array|string */
    public $response = Response::class;

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
