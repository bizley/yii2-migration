<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

use function in_array;
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

    /** @var bool|null */
    private $notNull;

    /** @var int|string|null */
    private $size;

    /** @var int|string|null */
    private $precision;

    /** @var int|string|null */
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

    /** @var string|null */
    private $append;

    /** @var string|null */
    private $comment;

    /** @var string|null */
    private $after;

    /** @var bool */
    private $first = false;

    /**
     * Returns name of the column.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets name for the column.
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns type of the column.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets type for the column.
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Checks whether the column can not be null.
     * @return bool|null
     */
    public function isNotNull(): ?bool
    {
        return $this->notNull;
    }

    /**
     * Sets the column to not be null.
     * @param bool|null $notNull
     */
    public function setNotNull(?bool $notNull): void
    {
        $this->notNull = $notNull;
    }

    /**
     * Returns size of the column.
     * @return int|string|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets size for the column.
     * @param int|string|null $size
     */
    public function setSize($size): void
    {
        $this->size = $size;
    }

    /**
     * Returns precision of the column.
     * @return int|string|null
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * Sets precision for the column.
     * @param int|string|null $precision
     */
    public function setPrecision($precision): void
    {
        $this->precision = $precision;
    }

    /**
     * Returns scale of the column.
     * @return int|string|null
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Sets scale for the column.
     * @param int|string|null $scale
     */
    public function setScale($scale): void
    {
        $this->scale = $scale;
    }

    /**
     * Checks whether the column is unique.
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Sets the uniqueness of the column.
     * @param bool $unique
     */
    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    /**
     * Checks whether the column is unsigned.
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * Sets the unsigned flag for the column.
     * @param bool $unsigned
     */
    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    /**
     * Returns default value of the column.
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Sets default value for the column.
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    /**
     * Checks whether the column is a primary key.
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    /**
     * Sets the primary key flag for the column.
     * @param bool|null $primaryKey
     */
    public function setPrimaryKey(?bool $primaryKey): void
    {
        $this->primaryKey = (bool)$primaryKey;
    }

    /**
     * Checks whether the column has autoincrement flag.
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * Sets the autoincrement flag for the column.
     * @param bool $autoIncrement
     */
    public function setAutoIncrement(bool $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * Returns the value of append statement of the column.
     * @return string|null
     */
    public function getAppend(): ?string
    {
        return $this->append;
    }

    /**
     * Sets the value for append statement for the column.
     * @param string|null $append
     */
    public function setAppend(?string $append): void
    {
        $this->append = $append;
    }

    /**
     * Returns the value for comment statement for the column.
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Sets the value for comment statement for the column.
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * Returns the value for after statement for the column.
     * @return string|null
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Sets the value for after statement for the column.
     * @param string|null $after
     */
    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    /**
     * Checks whether the column has first statement.
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->first;
    }

    /**
     * Sets the column for the first statement.
     * @param bool $first
     */
    public function setFirst(bool $first): void
    {
        $this->first = $first;
    }

    /**
     * Checks if column is a part of the primary key.
     * @param PrimaryKeyInterface $primaryKey
     * @return bool
     */
    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool
    {
        return in_array($this->name, $primaryKey->getColumns(), true);
    }

    /**
     * Checks if information of primary key is set in append statement.
     * @param string|null $schema
     * @return bool
     */
    public function isPrimaryKeyInfoAppended(?string $schema): bool
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
     * Prepares append statement based on the schema.
     * @param bool $primaryKey whether the column is primary key
     * @param bool $autoIncrement whether the column has autoincrement flag
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
     * Removes information of primary key in append statement and returns what is left.
     * @param string|null $schema
     * @return null|string
     */
    public function removeAppendedPrimaryKeyInfo(?string $schema): ?string
    {
        if ($this->append === null || $this->isPrimaryKeyInfoAppended($schema) === false) {
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

        $cleanedAppend = preg_replace('/\s+/', ' ', $cleanedAppend);
        if ($cleanedAppend !== null) {
            $cleanedAppend = trim($cleanedAppend);
        }

        return !empty($cleanedAppend) ? $cleanedAppend : null;
    }

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|int|null
     */
    abstract public function getLength(string $schema = null, string $engineVersion = null);

    /**
     * Sets length for the column.
     * @param string|int|array<string|int> $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    abstract public function setLength($value, string $schema = null, string $engineVersion = null): void;
}
