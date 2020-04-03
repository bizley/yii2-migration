<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\ArrangerInterface;

final class ArrangerStub implements ArrangerInterface
{
    public function __construct()
    {
    }

    public function arrangeTables(array $inputTables): void
    {
    }

    public function getTablesInOrder(): array
    {
        return ['test', 'test2'];
    }

    public function getReferencesToPostpone(): array
    {
        return ['test'];
    }
}
