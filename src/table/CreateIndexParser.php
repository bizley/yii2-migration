<?php

declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Parses PostgreSQL CREATE INDEX statement, like the ones generated for indexes that cannot be created with
 * createIndex() (with operator classes, predicates, expressions, etc.).
 */
final class CreateIndexParser
{
    private const IDENTIFIER = '(?:"(?:[^"]|"")+"|[\w$-]+)';

    /**
     * Returns the parsed statement data or null if the statement is not CREATE INDEX one.
     * @return array{table: string, name: string, unique: bool, definition: string}|null
     */
    public static function parse(string $sql): ?array
    {
        $identifier = self::IDENTIFIER;
        $pattern = '/^\s*CREATE\s+(?<unique>UNIQUE\s+)?INDEX\s+(?:CONCURRENTLY\s+)?(?:IF\s+NOT\s+EXISTS\s+)?'
            . '(?<name>\[\[.+?\]\]|' . $identifier . ')\s+'
            . 'ON\s+(?:ONLY\s+)?(?<table>\{\{.+?\}\}|' . $identifier . '(?:\.' . $identifier . ')?)\s*'
            . '(?<definition>(?:USING\s+\w+\s*)?\(.+?)\s*;?\s*$/is';

        if (\preg_match($pattern, $sql, $matches) !== 1) {
            return null;
        }

        $definition = $matches['definition'];
        if (\preg_match('/^USING\s+/i', $definition) === 1) {
            $definition = (string)\preg_replace('/^USING\s+/i', 'USING ', $definition);
        } else {
            $definition = 'USING btree ' . $definition;
        }

        return [
            'table' => \str_starts_with($matches['table'], '{{')
                ? $matches['table']
                : \implode('.', \array_map([self::class, 'unquote'], self::splitQualified($matches['table']))),
            'name' => \str_starts_with($matches['name'], '[[')
                ? \substr($matches['name'], 2, -2)
                : self::unquote($matches['name']),
            'unique' => $matches['unique'] !== '',
            'definition' => $definition,
        ];
    }

    /**
     * @return array<string>
     */
    private static function splitQualified(string $name): array
    {
        \preg_match_all('/' . self::IDENTIFIER . '/', $name, $matches);

        return $matches[0];
    }

    private static function unquote(string $identifier): string
    {
        if (\str_starts_with($identifier, '"')) {
            return \str_replace('""', '"', \substr($identifier, 1, -1));
        }

        return $identifier;
    }
}
