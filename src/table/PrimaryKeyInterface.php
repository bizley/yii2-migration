<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyInterface
{
    public function getName(): string;

    /** @return array<string> */
    public function getColumns(): array;

    public function isComposite(): bool;

    public function addColumn(string $name): void;
}
