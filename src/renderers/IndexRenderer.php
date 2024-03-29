<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

final class IndexRenderer implements IndexRendererInterface
{
    /** @var string */
    private $createIndexTemplate = '$this->createIndex(\'{indexName}\', \'{tableName}\', [{indexColumns}]{unique});';

    /** @var string */
    private $dropIndexTemplate = '$this->dropIndex(\'{indexName}\', \'{tableName}\');';

    /**
     * Renders the add index statement.
     */
    public function renderUp(IndexInterface $index, string $tableName, int $indent = 0): ?string
    {
        $template = \str_repeat(' ', $indent) . $this->createIndexTemplate;

        $indexColumns = $index->getColumns();
        $renderedColumns = [];
        foreach ($indexColumns as $indexColumn) {
            $renderedColumns[] = "'$indexColumn'";
        }

        return \str_replace(
            [
                '{indexName}',
                '{tableName}',
                '{indexColumns}',
                '{unique}',
            ],
            [
                $index->getName(),
                $tableName,
                \implode(', ', $renderedColumns),
                $index->isUnique() ? ', true' : '',
            ],
            $template
        );
    }

    /**
     * Renders the drop index statement.
     */
    public function renderDown(IndexInterface $index, string $tableName, int $indent = 0): ?string
    {
        $template = \str_repeat(' ', $indent) . $this->dropIndexTemplate;

        return \str_replace(
            [
                '{indexName}',
                '{tableName}'
            ],
            [
                $index->getName(),
                $tableName
            ],
            $template
        );
    }
}
