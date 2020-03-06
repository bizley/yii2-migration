<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\Arranger;
use bizley\migration\TableMapperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\db\Connection;

class ArrangerTest extends TestCase
{
    /** @var MockObject|Connection */
    private $db;
    /** @var TableMapperInterface|MockObject */
    private $tableMapper;
    /** @var Arranger */
    private $arranger;

    protected function setUp(): void
    {
        $this->tableMapper = $this->createMock(TableMapperInterface::class);
        $this->db = $this->createMock(Connection::class);
        $this->arranger = new Arranger($this->tableMapper, $this->db);
    }

    
}
