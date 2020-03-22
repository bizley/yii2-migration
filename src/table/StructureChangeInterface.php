<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureChangeInterface
{
    public function getMethod(): string;

    /** @return array|string|ColumnInterface|PrimaryKeyInterface|ForeignKeyInterface|IndexInterface Change value */
    public function getValue();
}
