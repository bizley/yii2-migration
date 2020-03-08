<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;
use function implode;
use function sprintf;
use function str_repeat;

class OldIndex
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $unique = false;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * Renders the index.
     * @param Structure $table
     * @param int $indent
     * @return string
     */
    public function render(Structure $table, int $indent = 8): string
    {
        return str_repeat(' ', $indent) . sprintf(
            '$this->createIndex(\'%s\', \'%s\', %s%s);',
            $this->name,
            $table->renderName(),
            count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']",
            $this->unique ? ', true' : ''
        );
    }
}
