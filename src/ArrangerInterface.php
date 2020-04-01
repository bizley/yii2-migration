<?php

declare(strict_types=1);

namespace bizley\migration;

interface ArrangerInterface
{
    /**
     * Arranges the tables in proper order based on the presence of the foreign keys.
     * @param array<string> $inputTables
     */
    public function arrangeTables(array $inputTables): void;

    /**
     * Returns the tables in proper order.
     * @return array<string>
     */
    public function getTablesInOrder(): array;

    /**
     * Returns the references that needs to be postponed. Foreign keys referring the tables in references must be
     * added in migration after the migration creating all the tables.
     * @return array<string>
     */
    public function getReferencesToPostpone(): array;
}
