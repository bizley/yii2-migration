<?php

declare(strict_types=1);

namespace bizley\migration;

interface ArrangerInterface
{
    public function arrangeMigrations(array $inputTables): void;

    public function getTablesInOrder(): array;

    public function getReferencesToPostpone(): array;
}
