<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ForeignKeyInterface
{
    /**
     * Returns name of the foreign key.
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Returns table name of the foreign key.
     * @return string
     */
    public function getTableName(): string;

    /**
     * Returns columns of the foreign key.
     * @return array<string>
     */
    public function getColumns(): array;

    /**
     * Returns referred table name of the foreign key.
     * @return string
     */
    public function getReferredTable(): string;

    /**
     * Returns referred column of the foreign key.
     * @return array<string>
     */
    public function getReferredColumns(): array;

    /**
     * Returns ON DELETE statement of the foreign key.
     * @return string|null
     */
    public function getOnDelete(): ?string;

    /**
     * Returns ON UPDATE statement of the foreign key.
     * @return string|null
     */
    public function getOnUpdate(): ?string;
}
