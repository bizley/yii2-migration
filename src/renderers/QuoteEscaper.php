<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

/**
 * Escapes values (like table, column, or key names read from the database) placed in single-quoted PHP strings of
 * the generated migrations, so they can not break (or inject code into) the generated file.
 */
final class QuoteEscaper
{
    /**
     * Escapes backslashes and single quotes so the value can be safely placed in a single-quoted PHP string.
     */
    public static function escape(string $value): string
    {
        return \str_replace(['\\', '\''], ['\\\\', '\\\''], $value);
    }

    /**
     * Renders the values as comma-separated single-quoted PHP strings.
     * @param array<string> $values
     */
    public static function renderList(array $values): string
    {
        return \implode(
            ', ',
            \array_map(static fn(string $value): string => "'" . self::escape($value) . "'", $values)
        );
    }
}
