<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyInterface
{
    public function getColumns(): array;
}
