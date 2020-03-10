<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

class ForeignKeyRenderer
{
    /**
     * @var ForeignKeyInterface
     */
    private $foreignKey;

    public function render(int $indent = 0): string
    {

    }

    /**
     * @return ForeignKeyInterface
     */
    public function getForeignKey(): ForeignKeyInterface
    {
        return $this->foreignKey;
    }

    /**
     * @param ForeignKeyInterface $foreignKey
     */
    public function setForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
    }
}
