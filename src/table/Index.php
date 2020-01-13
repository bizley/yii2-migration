<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;

use function count;
use function implode;
use function sprintf;
use function str_repeat;

class Index extends BaseObject
{
    /** @var string */
    public $name;

    /** @var bool */
    public $unique = false;

    /** @var array */
    public $columns = [];

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
