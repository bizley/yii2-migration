<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

trait PhpStringLiteralsTrait
{
    /** Name with characters that must be escaped in single-quoted PHP string and a template placeholder. */
    private static string $unusualName = 'a\'b\\c {tableName} {columns} "d" \\';

    /**
     * Parses the rendered code (throws ParseError when it is not valid PHP) and returns values of its string literals.
     * @return list<string>
     */
    private static function stringLiterals(string $code): array
    {
        $values = [];
        foreach (\token_get_all('<?php ' . $code, \TOKEN_PARSE) as $token) {
            if (\is_array($token) && $token[0] === \T_CONSTANT_ENCAPSED_STRING) {
                $values[] = eval('return ' . $token[1] . ';');
            }
        }

        return $values;
    }
}
