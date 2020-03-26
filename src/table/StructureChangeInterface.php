<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureChangeInterface
{
    public function getTable(): string;

    public function getMethod(): string;

    /** @return mixed Change value */
    public function getValue();
}
