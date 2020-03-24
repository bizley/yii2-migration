<?php

declare(strict_types=1);

namespace bizley\migration;

interface ArrangerInterface
{
    /** @param array<string> $inputTables */
    public function arrangeMigrations(array $inputTables): void;

    /** @return array<string> */
    public function getTablesInOrder(): array;

    /** @return array<string> */
    public function getReferencesToPostpone(): array;
}
