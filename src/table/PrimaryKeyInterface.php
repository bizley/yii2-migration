<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyInterface
{
    public function getName(): string;

    public function getColumns(): array;
}
