<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\db\Expression;

use function array_slice;
use function count;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function preg_split;
use function stripos;
use function strlen;
use function substr;
use function trim;
use function uksort;

use const PREG_SPLIT_NO_EMPTY;

class SqlColumnMapper
{
    /**
     * @var string
     */
    private $definition;

    /**
     * @var array<string, string>
     */
    private $typeMap;

    /**
     * @var array<string, mixed>
     */
    private $schema = [];

    /**
     * @param string $definition
     * @param array<string, string> $typeMap
     */
    private function __construct(string $definition, array $typeMap)
    {
        $this->definition = $definition;
        // make sure longer DB types are first to avoid mismatch in detectType()
        uksort($typeMap, static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $this->typeMap = $typeMap;
    }

    /**
     * @param string $definition
     * @param array<string, string> $typeMap
     * @return array<string, mixed>
     */
    public static function map(string $definition, array $typeMap): array
    {
        return (new self($definition, $typeMap))->getSchema();
    }

    /**
     * @return array<string, mixed>
     */
    private function getSchema(): array
    {
        $this->detectComment();
        $this->detectDefault();
        $this->detectFirst();
        $this->detectAfter();
        $this->detectNotNull();
        $this->detectNull();
        $this->detectAutoincrement();
        $this->detectPrimaryKey();
        $this->detectUnsigned();
        $this->detectUnique();
        $this->detectTypeAndLength();

        $this->prepareAppend();

        return $this->schema;
    }

    private function detectTypeAndLength(): void
    {
        foreach ($this->typeMap as $dbType => $yiiType) {
            if (stripos($this->definition, $dbType) !== false) {
                $this->schema['type'] = $yiiType;

                if (preg_match("/$dbType\\s?(\\(([a-z0-9',\\s_-]+)\\))?/i", $this->definition, $matches)) {
                    if ($dbType !== 'enum' && count($matches) >= 3) {
                        $this->schema['length'] = preg_replace('/\s+/', '', $matches[2]);
                    }

                    /** @infection-ignore-all */
                    $this->definition = (string)preg_replace(
                        "/$dbType\\s?(\\(([a-z0-9',\\s_-]+)\\))?/i",
                        '',
                        $this->definition
                    );
                }

                return;
            }
        }

        $this->schema['type'] = 'string';
    }

    private function cutOutDefinition(int $from, int $to): void
    {
        /** @infection-ignore-all */
        $this->definition = substr($this->definition, 0, $from) . substr($this->definition, $to);
    }

    private function detectComment(): void
    {
        if (preg_match('/COMMENT\s?([\'\"])/i', $this->definition, $matches)) {
            /** @infection-ignore-all */
            $cutFrom = (int)stripos($this->definition, 'COMMENT');
            [$cutTo, $sentence] = $this->findPart($matches[1], $this->definition, $cutFrom);
            $this->schema['comment'] = $sentence;

            $this->cutOutDefinition($cutFrom, $cutTo);
        }
    }

    private function detectDefault(): void
    {
        /** @infection-ignore-all */
        if (($cutFrom = stripos($this->definition, 'DEFAULT')) !== false) {
            if (preg_match('/DEFAULT\s?([\'\"])/i', $this->definition, $matches)) {
                [$cutTo, $sentence] = $this->findPart($matches[1], $this->definition, $cutFrom);
                $this->schema['default'] = $sentence;
                $this->cutOutDefinition($cutFrom, $cutTo);
            } elseif (preg_match('/DEFAULT\s?\(/i', $this->definition)) {
                [$cutTo, $sentence] = $this->findPart('(', $this->definition, $cutFrom);
                $this->schema['default'] = new Expression($sentence);
                $this->cutOutDefinition($cutFrom, $cutTo);
            } elseif (preg_match('/DEFAULT\s?[0-9]/i', $this->definition)) {
                [$cutTo, $sentence] = $this->findPart('1', $this->definition, $cutFrom);
                $this->schema['default'] = $sentence;
                $this->cutOutDefinition($cutFrom, $cutTo);
            } elseif (preg_match('/DEFAULT\s?[a-z]/i', $this->definition)) {
                [$cutTo, $sentence] = $this->findPart('', $this->definition, $cutFrom + 7);
                $this->schema['default'] = new Expression($sentence);
                $this->cutOutDefinition($cutFrom, $cutTo);
            }
        }
    }

    private function detectNotNull(): void
    {
        if (stripos($this->definition, 'NOT NULL') !== false) {
            $this->schema['isNotNull'] = true;
            $this->definition = str_ireplace('NOT NULL', '', $this->definition);
        }
    }

    private function detectNull(): void
    {
        if (stripos($this->definition, 'NULL') !== false) {
            $this->schema['isNotNull'] = false;
            $this->definition = str_ireplace('NULL', '', $this->definition);
        }
    }

    private function detectFirst(): void
    {
        if (stripos($this->definition, 'FIRST') !== false) {
            $this->schema['isFirst'] = true;
            $this->definition = str_ireplace('FIRST', '', $this->definition);
        }
    }

    private function detectAfter(): void
    {
        if (preg_match('/AFTER\s?`/i', $this->definition)) {
            /** @infection-ignore-all */
            $cutFrom = (int)stripos($this->definition, 'AFTER');
            /** @infection-ignore-all */
            [$cutTo, $sentence] = $this->findPart('`', $this->definition, $cutFrom + 5);
            $this->schema['after'] = $sentence;

            $this->cutOutDefinition($cutFrom, $cutTo);
        }
    }

    private function detectAutoincrement(): void
    {
        if (
            stripos($this->definition, 'AUTO_INCREMENT') !== false
            || stripos($this->definition, 'AUTOINCREMENT') !== false
        ) {
            $this->schema['autoIncrement'] = true;
            $this->definition = str_ireplace(['AUTO_INCREMENT', 'AUTOINCREMENT'], '', $this->definition);
        }
    }

    private function detectPrimaryKey(): void
    {
        if (
            stripos($this->definition, 'IDENTITY PRIMARY KEY') !== false
            || stripos($this->definition, 'PRIMARY KEY') !== false
        ) {
            $this->schema['isPrimaryKey'] = true;
            $this->definition = str_ireplace(['IDENTITY PRIMARY KEY', 'PRIMARY KEY'], '', $this->definition);
        }
    }

    private function detectUnsigned(): void
    {
        if (stripos($this->definition, 'UNSIGNED') !== false) {
            $this->schema['isUnsigned'] = true;
            $this->definition = str_ireplace('UNSIGNED', '', $this->definition);
        }
    }

    private function detectUnique(): void
    {
        if (stripos($this->definition, 'UNIQUE') !== false) {
            $this->schema['isUnique'] = true;
            $this->definition = str_ireplace('UNIQUE', '', $this->definition);
        }
    }

    /**
     * @param string $type
     * @param string $sentence
     * @param int $offset
     * @return array{0: int, 1: string}
     */
    private function findPart(string $type, string $sentence, int $offset = 0): array
    {
        $sentence = substr($sentence, $offset);

        /** @var array<int, string> $sentenceArray */
        $sentenceArray = preg_split('//u', $sentence, -1, PREG_SPLIT_NO_EMPTY);

        switch ($type) {
            case "'":
            case '"':
                [$end, $part] = $this->findQuotedPart($sentenceArray, $type);
                break;
            case '`':
                [$end, $part] = $this->findBacktickedPart($sentenceArray);
                break;
            case '(':
                [$end, $part] = $this->findParenthesizedPart($sentenceArray);
                break;
            case '1':
                [$end, $part] = $this->findNumericPart($sentenceArray);
                break;
            default:
                [$end, $part] = $this->findExpressionPart($sentenceArray);
        }

        return [$end ? $end + $offset : 0, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array{0: int, 1: string}
     */
    private function findQuotedPart(array $sentenceArray, string $quote): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        $consecutiveQuotes = 0;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && $char !== $quote) {
                continue;
            }

            if (!$collect && $char === $quote) {
                $collect = true;
                continue;
            }

            if ($collect) {
                if ($char !== $quote) {
                    if ($consecutiveQuotes > 0 && $consecutiveQuotes % 2 !== 0) {
                        break;
                    }
                    $consecutiveQuotes = 0;
                }
                $part .= $char;
                $end = $index + 1;
                if ($char === $quote) {
                    $consecutiveQuotes++;
                }
            }
        }
        if ($consecutiveQuotes > 0 && $consecutiveQuotes % 2 !== 0) {
            $part = substr($part, 0, -1);
        }

        return [(int)$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array{0: int, 1: string}
     */
    private function findBacktickedPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && $char !== '`') {
                continue;
            }

            if (!$collect && $char === '`') {
                $collect = true;
                continue;
            }

            if ($collect) {
                if ($char === '`' || preg_match('/\s/', $char)) {
                    break;
                }
                $part .= $char;
                $end = $index + 2;
            }
        }

        return [(int)$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array{0: int, 1: string}
     */
    private function findParenthesizedPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        $openedParenthesis = 0;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && $char !== '(') {
                continue;
            }

            if (!$collect && $char === '(') {
                $collect = true;
            }

            if ($collect) {
                $part .= $char;
                $end = $index + 1;
                if ($char === '(') {
                    $openedParenthesis++;
                } elseif ($char === ')') {
                    $openedParenthesis--;
                    if ($openedParenthesis === 0) {
                        break;
                    }
                }
            }
        }

        return [(int)$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array{0: int, 1: string}
     */
    private function findNumericPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && !is_numeric($char) && $char !== '-') {
                continue;
            }

            if (!$collect && (is_numeric($char) || $char === '-')) {
                $collect = true;
                $part .= $char;
                $end = $index + 1;
                continue;
            }

            if ($collect) {
                if (!is_numeric($char) && !preg_match('/[a-fA-F0-9x.]/', $char)) {
                    break;
                }
                $part .= $char;
                $end = $index + 1;
            }
        }

        return [(int)$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array{0: int, 1: string}
     */
    private function findExpressionPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && !preg_match('/[a-z]/i', $char)) {
                continue;
            }

            if (!$collect && preg_match('/[a-z]/i', $char)) {
                $collect = true;
            }

            if ($collect) {
                if (preg_match('/\s/', $char)) {
                    break;
                }

                if ($char === '(') {
                    [$parenthesisPartEnd, $parenthesisPart] = $this->findParenthesizedPart(
                        array_slice($sentenceArray, $index)
                    );
                    $part .= $parenthesisPart;
                    $end = $index + $parenthesisPartEnd;
                    break;
                }

                $part .= $char;
                $end = $index + 1;
            }
        }

        return [(int)$end, $part];
    }

    private function prepareAppend(): void
    {
        $append = trim($this->definition);

        if ($append !== '') {
            $this->schema['append'] = $append;
        }
    }
}
