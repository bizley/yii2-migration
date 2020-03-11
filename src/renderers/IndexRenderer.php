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
        $index = $this->getIndex();
        if ($index === null) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->getTemplate();

        $indexColumns = $index->getColumns();
        $renderedColumns = [];
        foreach ($indexColumns as $indexColumn) {
            $renderedColumns[] = "'$indexColumn'";
        }

        return str_replace(
            ['{indexName}', '{tableName}', '{indexColumns}', '{unique}'],
            [$index->getName(), $tableName, implode(', ', $renderedColumns), $index->isUnique() ? ', true' : ''],
            $template
        );
    }

    /**
     * @return IndexInterface
     */
    public function getIndex(): IndexInterface
    {
        return $this->index;
    }

    /**
     * @param IndexInterface $index
     */
    public function setIndex(IndexInterface $index): void
    {
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
