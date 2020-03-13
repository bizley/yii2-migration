<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class UnsignedPrimaryKeyColumn extends PrimaryKeyColumn
{
    public function __construct()
    {
        $this->setUnsigned(true);
    }
}
