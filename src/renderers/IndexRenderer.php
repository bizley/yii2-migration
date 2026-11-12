<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

final class IndexRenderer implements IndexRendererInterface
{
    private string $createIndexTemplate = '$this->createIndex(\'{indexName}\', \'{tableName}\', [{indexColumns}]{unique});';

    private string $executeCreateIndexTemplate
        = '$this->execute(\'CREATE{unique} INDEX {indexName} ON {tableName} {definition}\');';

    private string $dropIndexTemplate = '$this->dropIndex(\'{indexName}\', \'{tableName}\');';

    /**
     * Renders the add index statement.
     */
    public function renderUp(IndexInterface $index, string $tableName, int $indent = 0): string
    {
        $definition = $index->getDefinition();
        if ($definition !== null) {
            // single pass replacement, so the replaced values are not processed again
            return \str_repeat(' ', $indent) . \strtr(
                $this->executeCreateIndexTemplate,
                [
                    '{unique}' => $index->isUnique() ? ' UNIQUE' : '',
                    '{indexName}' => QuoteEscaper::escape($this->renderSqlIndexName($index->getName() ?? '')),
                    '{tableName}' => QuoteEscaper::escape($tableName),
                    '{definition}' => QuoteEscaper::escape($definition),
                ]
            );
        }

        $template = \str_repeat(' ', $indent) . $this->createIndexTemplate;

        return \strtr(
            $template,
            [
                '{indexName}' => QuoteEscaper::escape($index->getName() ?? ''),
                '{tableName}' => QuoteEscaper::escape($tableName),
                '{indexColumns}' => QuoteEscaper::renderList($index->getColumns()),
                '{unique}' => $this->renderType($index),
            ]
        );
    }

    /**
     * Renders the drop index statement.
     */
    public function renderDown(IndexInterface $index, string $tableName, int $indent = 0): string
    {
        $template = \str_repeat(' ', $indent) . $this->dropIndexTemplate;

        return \strtr(
            $template,
            [
                '{indexName}' => QuoteEscaper::escape($index->getName() ?? ''),
                '{tableName}' => QuoteEscaper::escape($tableName),
            ]
        );
    }

    /**
     * Renders the fourth argument of createIndex(): the unique flag or the index method.
     * Only B-tree indexes can be unique in PostgreSQL, so both are never needed at the same time.
     */
    private function renderType(IndexInterface $index): string
    {
        if ($index->isUnique()) {
            return ', true';
        }

        $method = $index->getMethod();

        return $method !== null ? ", '" . QuoteEscaper::escape($method) . "'" : '';
    }

    /**
     * Renders the index name for raw SQL. Yii's [[...]] placeholder supports only names made of word characters,
     * hyphens, dots, and spaces - other names are quoted directly (PostgreSQL style).
     */
    private function renderSqlIndexName(string $name): string
    {
        if (\preg_match('/^[\w\-. ]+$/', $name) === 1) {
            return '[[' . $name . ']]';
        }

        return '"' . \str_replace('"', '""', $name) . '"';
    }
}
