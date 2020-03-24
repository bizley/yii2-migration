<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface IndexInterface
{
    public function getName(): string;

    /** @return array<string> */
    public function getColumns(): array;

    public function isUnique(): bool;
}
