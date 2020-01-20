<?php

declare(strict_types=1);

namespace bizley\tests;

use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function createYiiMock(string $className, array $config = []): MockObject
    {
        return $this->getMockBuilder($className)
            ->setConstructorArgs($config)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();
    }
}
