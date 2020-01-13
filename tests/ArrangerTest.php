<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\Arranger;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;

class ArrangerTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowInvalidConfigExceptionWhenNoConnectionIsPassed(): void
    {
        $this->expectException(InvalidConfigException::class);

        new Arranger();
    }
}
