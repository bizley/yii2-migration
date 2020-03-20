<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

use function implode;
use function str_repeat;
use function str_replace;

final class IndexRenderer implements IndexRendererInterface
{
    /**
     * @var string
     */
    private $template = '$this->createIndex(\'{indexName}\', \'{tableName}\', [{indexColumns}]{unique});';

    public function render(IndexInterface $index, string $tableName, int $indent = 0): ?string
    {
        $template = str_repeat(' ', $indent) . $this->template;

        $indexColumns = $index->getColumns();
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
                $index->getName(),
                $tableName,
                implode(', ', $renderedColumns),
                $index->isUnique() ? ', true' : '',
            ],
            $template
        );
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
