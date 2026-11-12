<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

abstract class Column
{
    private string $name;

    private string $type;

    private bool $notNull = false;

    private int|string|null $size = null;

    private int|string|null $precision = null;

    private int|string|null $scale = null;

    private bool $unique = false;

    private bool $unsigned = false;

    private mixed $default = null;

    private bool $primaryKey = false;

    private bool $autoIncrement = false;

    private ?string $append = null;

    private ?string $comment = null;

    private ?string $after = null;

    private bool $first = false;

    /**
     * Returns name of the column.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets name for the column.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns type of the column.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets type for the column.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Checks whether the column can not be null.
     */
    public function isNotNull(): ?bool
    {
        return $this->notNull;
    }

    /**
     * Sets the column to not be null.
     */
    public function setNotNull(?bool $notNull): void
    {
        // this makes sure notNull is strictly `boolean` without introducing BC break for this method
        $this->notNull = $notNull ?? false;
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
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Sets the uniqueness of the column.
     */
    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    /**
     * Checks whether the column is unsigned.
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * Sets the unsigned flag for the column.
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
     */
    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    /**
     * Sets the primary key flag for the column.
     */
    public function setPrimaryKey(?bool $primaryKey): void
    {
        $this->primaryKey = (bool)$primaryKey;
    }

    /**
     * Checks whether the column has autoincrement flag.
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * Sets the autoincrement flag for the column.
     */
    public function setAutoIncrement(bool $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * Returns the value of append statement of the column.
     */
    public function getAppend(): ?string
    {
        return $this->append;
    }

    /**
     * Sets the value for append statement for the column.
     */
    public function setAppend(?string $append): void
    {
        $this->append = $append;
    }

    /**
     * Returns the value for comment statement for the column.
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Sets the value for comment statement for the column.
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * Returns the value for after statement for the column.
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Sets the value for after statement for the column.
     */
    public function setAfter(?string $after): void
    {
        $this->after = $after;
    }

    /**
     * Checks whether the column has first statement.
     */
    public function isFirst(): bool
    {
        return $this->first;
    }

    /**
     * Sets the column for the first statement.
     */
    public function setFirst(bool $first): void
    {
        $this->first = $first;
    }

    /**
     * Checks if column is a part of the primary key.
     */
    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool
    {
        return \in_array($this->name, $primaryKey->getColumns(), true);
    }

    /**
     * Checks if information of primary key is set in append statement.
     */
    public function isPrimaryKeyInfoAppended(?string $schema): bool
    {
        $append = $this->getAppend();
        if (empty($append)) {
            return false;
        }

        if (\stripos($append, 'PRIMARY KEY') !== false) {
            return !($schema === Schema::MSSQL && \stripos($append, 'IDENTITY') === false);
        }

        return false;
    }

    /**
     * Prepares append statement based on the schema.
     * @param bool $primaryKey whether the column is primary key
     * @param bool $autoIncrement whether the column has autoincrement flag
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement, ?string $schema = null): ?string
    {
        $append = match ($schema) {
            Schema::MSSQL => $primaryKey ? 'IDENTITY PRIMARY KEY' : '',
            Schema::OCI, Schema::PGSQL => $primaryKey ? 'PRIMARY KEY' : '',
            Schema::SQLITE => \trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : '')),
            default => \trim(($autoIncrement ? 'AUTO_INCREMENT ' : '') . ($primaryKey ? 'PRIMARY KEY' : '')),
        };

        return empty($append) ? null : $append;
    }

    /**
     * Escapes backslashes and single quotes so the value can be safely placed in a single-quoted PHP string.
     */
    public function escapeQuotes(string $value): string
    {
        return \str_replace(['\\', '\''], ['\\\\', '\\\''], $value);
    }

    /**
     * Removes information of primary key in append statement and returns what is left.
     */
    public function removeAppendedPrimaryKeyInfo(?string $schema): ?string
    {
        if ($this->append === null || $this->isPrimaryKeyInfoAppended($schema) === false) {
            return $this->append;
        }

        $cleanedAppend = match ($schema) {
            Schema::MSSQL => \str_ireplace(['PRIMARY KEY', 'IDENTITY'], '', $this->append),
            Schema::OCI, Schema::PGSQL => \str_ireplace('PRIMARY KEY', '', $this->append),
            Schema::SQLITE => \str_ireplace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $this->append),
            default => \str_ireplace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $this->append),
        };

        $cleanedAppend = \preg_replace('/\s+/', ' ', $cleanedAppend);
        if ($cleanedAppend !== null) {
            $cleanedAppend = \trim($cleanedAppend);
        }

        return !empty($cleanedAppend) ? $cleanedAppend : null;
    }

    /**
     * Returns length of the column.
     * @return string|int|null
     */
    abstract public function getLength(?string $schema = null, ?string $engineVersion = null);

    /**
     * Sets length for the column.
     * @param string|int|array<string|int>|null $value
     */
    abstract public function setLength($value, ?string $schema = null, ?string $engineVersion = null): void;
}
