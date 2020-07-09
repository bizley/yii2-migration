<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\db\Expression;

use function array_key_exists;
use function array_slice;
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

                if (preg_match("/$dbType\\s?(\\(([a-z0-9',\\s]+)\\))?/i", $this->definition, $matches)) {
                    if ($dbType !== 'enum' && array_key_exists(2, $matches)) {
                        $this->schema['length'] = preg_replace('/\s+/', '', $matches[2]);
                    }

                    $this->definition = (string)preg_replace(
                        "/$dbType\\s?(\\(([a-z0-9',\\s]+)\\))?/i",
                        '',
                        $this->definition
                    );
                }

                return;
            }
        }

        $this->schema['type'] = 'string';
    }

    private function detectComment(): void
    {
        if (preg_match('/COMMENT\s?\'/i', $this->definition, $matches)) {
            $cutFrom = (int)stripos($this->definition, 'COMMENT');
            [$cutTo, $sentence] = $this->findPart("'", $this->definition, $cutFrom);
            $this->schema['comment'] = $sentence;
            $definitionFirstPart = substr($this->definition, 0, $cutFrom);
            $definitionLastPart = substr($this->definition, (int)$cutTo);
            $this->definition = $definitionFirstPart . $definitionLastPart;
        }
    }

    private function detectDefault(): void
    {
        $detected = false;
        $sentence = null;
        $cutFrom = $cutTo = 0;

        if (preg_match('/DEFAULT\s?\'/i', $this->definition, $matches)) {
            $detected = true;
            $cutFrom = (int)stripos($this->definition, 'DEFAULT');
            [$cutTo, $sentence] = $this->findPart("'", $this->definition, $cutFrom);
            $this->schema['default'] = $sentence;
        } elseif (preg_match('/DEFAULT\s?\(/i', $this->definition, $matches)) {
            $detected = true;
            $cutFrom = (int)stripos($this->definition, 'DEFAULT');
            [$cutTo, $sentence] = $this->findPart('(', $this->definition, $cutFrom);
            $this->schema['default'] = new Expression((string)$sentence);
        } elseif (preg_match('/DEFAULT\s?[0-9]/i', $this->definition, $matches)) {
            $detected = true;
            $cutFrom = (int)stripos($this->definition, 'DEFAULT');
            [$cutTo, $sentence] = $this->findPart('1', $this->definition, $cutFrom);
            $this->schema['default'] = $sentence;
        } elseif (preg_match('/DEFAULT\s?[a-z]/i', $this->definition, $matches)) {
            $detected = true;
            $cutFrom = (int)stripos($this->definition, 'DEFAULT');
            [$cutTo, $sentence] = $this->findPart('', $this->definition, $cutFrom + 7);
            $this->schema['default'] = new Expression((string)$sentence);
        }

        if ($detected) {
            $definitionFirstPart = substr($this->definition, 0, $cutFrom);
            $definitionLastPart = substr($this->definition, (int)$cutTo);
            $this->definition = $definitionFirstPart . $definitionLastPart;
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
        if (preg_match('/AFTER\s?`/i', $this->definition, $matches)) {
            $cutFrom = (int)stripos($this->definition, 'AFTER');
            [$cutTo, $sentence] = $this->findPart('`', $this->definition, $cutFrom + 5);
            $this->schema['after'] = $sentence;
            $definitionFirstPart = substr($this->definition, 0, $cutFrom);
            $definitionLastPart = substr($this->definition, (int)$cutTo);
            $this->definition = $definitionFirstPart . $definitionLastPart;
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
     * @return array<int|string>
     */
    private function findPart(string $type, string $sentence, int $offset = 0): array
    {
        $sentence = substr($sentence, $offset);

        $sentenceArray = preg_split('//u', $sentence, -1, PREG_SPLIT_NO_EMPTY);
        if ($sentenceArray === false) {
            $sentenceArray = [];
        }

        switch ($type) {
            case "'":
                [$end, $part] = $this->findSingleQuotedPart($sentenceArray);
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

        return [$end ? (int)$end + $offset : 0, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array<int|string>
     */
    private function findSingleQuotedPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        $consecutiveQuotes = 0;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && $char !== "'") {
                continue;
            }

            if (!$collect && $char === "'") {
                $collect = true;
                continue;
            }

            if ($collect) {
                if ($char !== "'") {
                    if ($consecutiveQuotes > 0 && $consecutiveQuotes % 2 !== 0) {
                        break;
                    }
                    $consecutiveQuotes = 0;
                }
                $part .= $char;
                $end = $index + 1;
                if ($char === "'") {
                    $consecutiveQuotes++;
                }
            }
        }
        if ($consecutiveQuotes > 0 && $consecutiveQuotes % 2 !== 0) {
            $part = substr($part, 0, -1);
        }

        return [$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array<int|string>
     */
    private function findBacktickedPart(array $sentenceArray): array
    {
        $part = '';
        $end = 0;
        $collect = false;
        foreach ($sentenceArray as $index => $char) {
            if (!$collect && $char !== '`' && !preg_match('/[a-z]/i', $char)) {
                continue;
            }

            if (!$collect && ($char === '`' || preg_match('/[a-z]/i', $char))) {
                $collect = true;
                if ($char !== '`') {
                    $part .= $char;
                    $end = $index + 1;
                }
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

        return [$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array<int|string>
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

        return [$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array<int|string>
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
                if (!is_numeric($char) && $char !== '.') {
                    break;
                }
                $part .= $char;
                $end = $index + 1;
            }
        }

        return [$end, $part];
    }

    /**
     * @param string[] $sentenceArray
     * @return array<int|string>
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
                    $end = $parenthesisPartEnd;
                    break;
                }

                $part .= $char;
                $end = $index + 1;
            }
        }

        return [$end, $part];
    }

    private function prepareAppend(): void
    {
        $append = trim($this->definition);

        if ($append !== '') {
            $this->schema['append'] = $append;
        }
    }
}
