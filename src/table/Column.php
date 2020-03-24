<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

use function in_array;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function stripos;
use function trim;

abstract class Column
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $defaultMapping;

    /** @var bool|null */
    private $notNull;

    /** @var int */
    private $size;

    /** @var int */
    private $precision;

    /** @var int */
    private $scale;

    /** @var bool */
    private $unique = false;

    /** @var bool */
    private $unsigned = false;

    /** @var mixed */
    private $default;

    /** @var bool */
    private $primaryKey = false;

    /** @var bool */
    private $autoIncrement = false;

    /** @var string */
    private $append;

    /** @var string */
    private $comment;

    /** @var string */
    private $after;

    /** @var bool */
    private $first = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDefaultMapping(): ?string
    {
        return $this->defaultMapping;
    }

    public function setDefaultMapping(string $defaultMapping): void
    {
        $this->defaultMapping = $defaultMapping;
    }

    public function isNotNull(): ?bool
    {
        return $this->notNull;
    }

    public function setNotNull(?bool $notNull): void
    {
        $this->notNull = $notNull;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    /** @param int|string|null $size */
    public function setSize($size): void
    {
        if ($size !== null) {
            $this->size = (int)$size;
        } else {
            $this->size = null;
        }
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /** @param int|string|null $precision */
    public function setPrecision($precision): void
    {
        if ($precision !== null) {
            $this->precision = (int)$precision;
        } else {
            $this->precision = null;
        }
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    /** @param int|string|null $scale */
    public function setScale($scale): void
    {
        if ($scale !== null) {
            $this->scale = (int)$scale;
        } else {
            $this->scale = null;
        }
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    /** @return mixed */
    public function getDefault()
    {
        return $this->default;
    }

    /** @param mixed $default */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey(bool $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(bool $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    public function getAppend(): string
    {
        return $this->append;
    }

    public function setAppend(?string $append): void
    {
        $this->append = $append;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }

    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    public function isFirst(): bool
    {
        return $this->first;
    }

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
            return !($schema === Schema::MSSQL && stripos($append, 'IDENTITY') === false);
        }

        return false;
    }

    /**
     * Prepares append SQL based on schema.
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @param string|null $schema
     * @return string|null
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement, string $schema = null): ?string
    {
        switch ($schema) {
            case Schema::MSSQL:
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;

            case Schema::OCI:
            case Schema::PGSQL:
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;

            case Schema::SQLITE:
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;

            case Schema::CUBRID:
            case Schema::MYSQL:
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
            case Schema::MSSQL:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'IDENTITY'], '', $this->append);
                break;

            case Schema::OCI:
            case Schema::PGSQL:
                $cleanedAppend = str_ireplace('PRIMARY KEY', '', $this->append);
                break;

            case Schema::SQLITE:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $this->append);
                break;

            case Schema::CUBRID:
            case Schema::MYSQL:
            default:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $this->append);
        }

        $cleanedAppend = trim(preg_replace('/\s+/', ' ', $cleanedAppend));

        return !empty($cleanedAppend) ? $cleanedAppend : null;
    }

    abstract public function getLength(string $schema = null, string $engineVersion = null);

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
