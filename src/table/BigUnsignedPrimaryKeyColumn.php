<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class BigUnsignedPrimaryKeyColumn extends BigPrimaryKeyColumn
{
    public function __construct()
    {
        $this->setUnsigned(true);
    }
}
