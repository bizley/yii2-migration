<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\Arranger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\db\Connection;

class ArrangerTest extends TestCase
{
    /** @var MockObject|Connection */
    private $db;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->arranger = new Arranger();
    }
}
