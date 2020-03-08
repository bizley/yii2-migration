<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;

use function in_array;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function stripos;
use function trim;

abstract class Column
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $defaultMapping;

    /**
     * @var bool|null
     */
    private $notNull;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var int
     */
    private $scale;

    /**
     * @var bool
     */
    private $unique = false;

    /**
     * @var bool
     */
    private $unsigned = false;

    /**
     * @var mixed
     */
    private $default;

    /**
     * @var bool
     */
    private $primaryKey = false;

    /**
     * @var bool
     */
    private $autoIncrement = false;

    /**
     * @var string
     */
    private $append;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $after;

    /**
     * @var bool
     */
    private $first = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDefaultMapping(): string
    {
        return $this->defaultMapping;
    }

    /**
     * @param string $defaultMapping
     */
    public function setDefaultMapping(string $defaultMapping): void
    {
        $this->defaultMapping = $defaultMapping;
    }

    /**
     * @return bool|null
     */
    public function isNotNull(): ?bool
    {
        return $this->notNull;
    }

    /**
     * @param bool|null $notNull
     */
    public function setNotNull(?bool $notNull): void
    {
        $this->notNull = $notNull;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int|string|null $size
     */
    public function setSize($size): void
    {
        if ($size !== null) {
            $this->size = (int)$size;
        } else {
            $this->size = null;
        }
    }

    /**
     * @return int|null
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * @param int|string|null $precision
     */
    public function setPrecision($precision): void
    {
        if ($precision !== null) {
            $this->precision = (int)$precision;
        } else {
            $this->precision = null;
        }
    }

    /**
     * @return int|null
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * @param int|string|null $scale
     */
    public function setScale($scale): void
    {
        if ($scale !== null) {
            $this->scale = (int)$scale;
        } else {
            $this->scale = null;
        }
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @param bool $unsigned
     */
    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $primaryKey
     */
    public function setPrimaryKey(bool $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * @param bool $autoIncrement
     */
    public function setAutoIncrement(bool $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return string
     */
    public function getAppend(): string
    {
        return $this->append;
    }

    /**
     * @param string|null $append
     */
    public function setAppend(?string $append): void
    {
        $this->append = $append;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getAfter(): string
    {
        return $this->after;
    }

    /**
     * @param string|null $after
     */
    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->first;
    }

    /**
     * @param bool $first
     */
    public function setFirst(bool $first): void
    {
        $this->first = $first;
    }

    /**
     * Checks if column is a part of primary key.
     * @param PrimaryKeyInterface $primaryKey
     * @return bool
     */
    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool
    {
        return in_array($this->name, $primaryKey->getColumns(), true);
    }

    /**
     * Checks if information of primary key is set in append property.
     * @param string $schema
     * @return bool
     */
    public function isPrimaryKeyInfoAppended(string $schema): bool
    {
        $append = $this->getAppend();
        if (empty($append)) {
            return false;
        }

        if (stripos($append, 'PRIMARY KEY') !== false) {
            return !($schema === SchemaEnum::MSSQL && stripos($append, 'IDENTITY') === false);
        }

        return false;
    }

    /**
     * Prepares append SQL based on schema.
     * @param string $schema
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string|null
     */
    public function prepareSchemaAppend(string $schema, bool $primaryKey, bool $autoIncrement): ?string
    {
        switch ($schema) {
            case SchemaEnum::MSSQL:
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;

            case SchemaEnum::OCI:
            case SchemaEnum::PGSQL:
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;

            case SchemaEnum::SQLITE:
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;

            case SchemaEnum::CUBRID:
            case SchemaEnum::MYSQL:
            default:
                $append = trim(($autoIncrement ? 'AUTO_INCREMENT ' : '') . ($primaryKey ? 'PRIMARY KEY' : ''));
        }

        return empty($append) ? null : $append;
    }

    /**
     * Escapes single quotes.
     * @param string $value
     * @return string
     */
    public function escapeQuotes(string $value): string
    {
        return str_replace('\'', '\\\'', $value);
    }

    /**
     * Removes information of primary key in append property and returns what is left.
     * @param string $schema
     * @return null|string
     */
    public function removeAppendedPrimaryKeyInfo(string $schema): ?string
    {
        if ($this->isPrimaryKeyInfoAppended($schema) === false) {
            return $this->append;
        }

        switch ($schema) {
            case SchemaEnum::MSSQL:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'IDENTITY'], '', $this->append);
                break;

            case SchemaEnum::OCI:
            case SchemaEnum::PGSQL:
                $cleanedAppend = str_ireplace('PRIMARY KEY', '', $this->append);
                break;

            case SchemaEnum::SQLITE:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $this->append);
                break;

            case SchemaEnum::CUBRID:
            case SchemaEnum::MYSQL:
            default:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $this->append);
        }

        $cleanedAppend = trim(preg_replace('/\s+/', ' ', $cleanedAppend));

        return !empty($cleanedAppend) ? $cleanedAppend : null;
    }

    /**
     * @param bool $generalSchema
     * @return string|null
     */
    public function getRenderLength(bool $generalSchema): ?string
    {
        $length = $this->getLength();

        if ($length === null) {
            return null;
        }

        if ($generalSchema === false) {
            if ($length === 'max') {
                return '\'max\'';
            }
            return (string)$length;
        }

        if (str_replace(' ', '', (string)$length) !== $this->getDefaultLength()) {
            if ($length === 'max') {
                return '\'max\'';
            }
            return (string)$length;
        }

        return null;
    }

    private function getDefaultLength(): ?string
    {
        if ($this->defaultMapping !== null) {
            if (preg_match('/\(([\d,]+)\)/', $this->defaultMapping, $matches)) {
                return $matches[1];
            }
            if (preg_match('/\(max\)/', $this->defaultMapping)) {
                // MSSQL
                return 'max';
            }
        }

        return null;
    }
}
