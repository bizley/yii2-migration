<?php

declare(strict_types=1);

namespace bizley\tests\unit\stubs;

use bizley\migration\ArrangerInterface;
use bizley\migration\TableMapperInterface;

final class ArrangerStub implements ArrangerInterface
{
    public function __construct(TableMapperInterface $mapper)
    {
    }

    public function arrangeMigrations(array $inputTables): void
    {
    }

    public function getTablesInOrder(): array
    {
        return [];
    }

    public function getReferencesToPostpone(): array
    {
        return ['a', 'b'];
    }
}
