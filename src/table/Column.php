<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function stripos;
use function trim;

abstract class Column implements ColumnInterface
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
    private $isNotNull;

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
    private $isUnique = false;

    /**
     * @var bool
     */
    private $isUnsigned = false;

    /**
     * @var mixed
     */
    private $default;

    /**
     * @var bool
     */
    private $isPrimaryKey = false;

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
    private $schema;

    /**
     * @var string
     */
    private $after;

    /**
     * @var bool
     */
    private $isFirst = false;

    /**
     * @var string
     */
    private $engineVersion;

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    abstract public function setLength($value): void;

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    abstract public function getLength();

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
    public function getIsNotNull(): ?bool
    {
        return $this->isNotNull;
    }

    /**
     * @param bool|null $isNotNull
     */
    public function setIsNotNull(?bool $isNotNull): void
    {
        $this->isNotNull = $isNotNull;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return int|null
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * @param int|null $precision
     */
    public function setPrecision(?int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * @return int|null
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * @param int|null $scale
     */
    public function setScale(?int $scale): void
    {
        $this->scale = $scale;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @param bool $isUnique
     */
    public function setIsUnique(bool $isUnique): void
    {
        $this->isUnique = $isUnique;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    /**
     * @param bool $isUnsigned
     */
    public function setIsUnsigned(bool $isUnsigned): void
    {
        $this->isUnsigned = $isUnsigned;
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
        return $this->isPrimaryKey;
    }

    /**
     * @param bool $isPrimaryKey
     */
    public function setIsPrimaryKey(bool $isPrimaryKey): void
    {
        $this->isPrimaryKey = $isPrimaryKey;
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
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     */
    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
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
        return $this->isFirst;
    }

    /**
     * @param bool $isFirst
     */
    public function setIsFirst(bool $isFirst): void
    {
        $this->isFirst = $isFirst;
    }

    /**
     * @return string
     */
    public function getEngineVersion(): string
    {
        return $this->engineVersion;
    }

    /**
     * @param string $engineVersion
     */
    public function setEngineVersion(string $engineVersion): void
    {
        $this->engineVersion = $engineVersion;
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
     * @return bool
     */
    public function isPrimaryKeyInfoAppended(): bool
    {
        if (empty($this->append)) {
            return false;
        }

        if (stripos($this->append, 'PRIMARY KEY') !== false) {
            return !($this->schema === Structure::SCHEMA_MSSQL && stripos($this->append, 'IDENTITY') === false);
        }

        return false;
    }

    /**
     * Prepares append SQL based on schema.
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string|null
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement): ?string
    {
        switch ($this->schema) {
            case Structure::SCHEMA_MSSQL:
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;

            case Structure::SCHEMA_OCI:
            case Structure::SCHEMA_PGSQL:
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;

            case Structure::SCHEMA_SQLITE:
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;

            case Structure::SCHEMA_CUBRID:
            case Structure::SCHEMA_MYSQL:
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
     * @return null|string
     */
    public function removeAppendedPrimaryKeyInfo(): ?string
    {
        if ($this->isPrimaryKeyInfoAppended() === false) {
            return $this->append;
        }

        switch ($this->schema) {
            case Structure::SCHEMA_MSSQL:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'IDENTITY'], '', $this->append);
                break;

            case Structure::SCHEMA_OCI:
            case Structure::SCHEMA_PGSQL:
                $cleanedAppend = str_ireplace('PRIMARY KEY', '', $this->append);
                break;

            case Structure::SCHEMA_SQLITE:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $this->append);
                break;

            case Structure::SCHEMA_CUBRID:
            case Structure::SCHEMA_MYSQL:
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
