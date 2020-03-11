<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

use function implode;
use function str_repeat;
use function str_replace;

class IndexRenderer implements IndexRendererInterface
{
    /**
     * @var IndexInterface
     */
    private $index;

    /**
     * @var string
     */
    private $template = '$this->createIndex(\'{indexName}\', \'{tableName}\', [{indexColumns}]{unique});';

    public function render(string $tableName, int $indent = 0): ?string
    {
        if ($this->index === null) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->template;

        $indexColumns = $this->index->getColumns();
        $renderedColumns = [];
        foreach ($indexColumns as $indexColumn) {
            $renderedColumns[] = "'$indexColumn'";
        }

        return str_replace(
            [
                '{indexName}',
                '{tableName}',
                '{indexColumns}',
                '{unique}',
            ],
            [
                $this->index->getName(),
                $tableName,
                implode(', ', $renderedColumns),
                $this->index->isUnique() ? ', true' : '',
            ],
            $template
        );
    }

    /**
     * @param IndexInterface $index
     */
    public function setIndex(IndexInterface $index): void
    {
        $this->index = $index;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
