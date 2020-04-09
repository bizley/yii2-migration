<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ColumnInterface
{
    /**
     * Returns name of the column.
     * @return string
     */
    public function getName(): string;

    /**
     * Sets name for the column.
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * Checks whether the column is a primary key.
     * @return bool
     */
    public function isPrimaryKey(): bool;

    /**
     * Sets the primary key flag for the column.
     * @param bool|null $primaryKey
     */
    public function setPrimaryKey(?bool $primaryKey): void;

    /**
     * Checks if column is a part of the primary key.
     * @param PrimaryKeyInterface $primaryKey
     * @return bool
     */
    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool;

    /**
     * Checks if information of primary key is set in append statement.
     * @param string|null $schema
     * @return bool
     */
    public function isPrimaryKeyInfoAppended(?string $schema): bool;

    /**
     * Returns the value of append statement of the column.
     * @return string|null
     */
    public function getAppend(): ?string;

    /**
     * Sets the value for append statement for the column.
     * @param string|null $append
     */
    public function setAppend(?string $append): void;

    /**
     * Prepares append statement based on the schema.
     * @param bool $primaryKey whether the column is primary key
     * @param bool $autoIncrement whether the column has autoincrement flag
     * @param string|null $schema
     * @return string|null
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement, string $schema = null): ?string;

    /**
     * Removes information of primary key in append statement and returns what is left.
     * @param string|null $schema
     * @return null|string
     */
    public function removeAppendedPrimaryKeyInfo(?string $schema): ?string;

    /**
     * Sets the value for comment statement for the column.
     * @param string|null $comment
     */
    public function setComment(?string $comment): void;

    /**
     * Returns the value for comment statement for the column.
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Returns size of the column.
     * @return int|string|null
     */
    public function getSize();

    /**
     * Sets size for the column.
     * @param int|string|null $size
     */
    public function setSize($size): void;

    /**
     * Returns precision of the column.
     * @return int|string|null
     */
    public function getPrecision();

    /**
     * Sets precision for the column.
     * @param int|string|null $precision
     */
    public function setPrecision($precision): void;

    /**
     * Returns scale of the column.
     * @return int|string|null
     */
    public function getScale();

    /**
     * Sets scale for the column.
     * @param int|string|null $scale
     */
    public function setScale($scale): void;

    /**
     * Checks whether the column can not be null.
     * @return bool|null
     */
    public function isNotNull(): ?bool;

    /**
     * Sets the column to not be null.
     * @param bool|null $notNull
     */
    public function setNotNull(?bool $notNull): void;

    /**
     * Returns default value of the column.
     * @return mixed
     */
    public function getDefault();

    /**
     * Sets default value for the column.
     * @param mixed $default
     */
    public function setDefault($default): void;

    /**
     * Checks whether the column is unsigned.
     * @return bool
     */
    public function isUnsigned(): bool;

    /**
     * Sets the unsigned flag for the column.
     * @param bool $unsigned
     */
    public function setUnsigned(bool $unsigned): void;

    /**
     * Checks whether the column is unique.
     * @return bool
     */
    public function isUnique(): bool;

    /**
     * Sets the uniqueness of the column.
     * @param bool $unique
     */
    public function setUnique(bool $unique): void;

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|int|null
     */
    public function getLength(string $schema = null, string $engineVersion = null);

    /**
     * Sets length for the column.
     * @param string|int|array<string|int>|null $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void;

    /**
     * Returns default column definition.
     * @return string
     */
    public function getDefinition(): string;

    /**
     * Returns the value for after statement for the column.
     * @return string|null
     */
    public function getAfter(): ?string;

    /**
     * Sets the value for after statement for the column.
     * @param string|null $after
     */
    public function setAfter(?string $after): void;

    /**
     * Checks whether the column has first statement.
     * @return bool
     */
    public function isFirst(): bool;

    /**
     * Sets the column for the first statement.
     * @param bool $first
     */
    public function setFirst(bool $first): void;

    /**
     * Checks whether the column has autoincrement flag.
     * @return bool
     */
    public function isAutoIncrement(): bool;

    /**
     * Sets the autoincrement flag for the column.
     * @param bool $autoIncrement
     */
    public function setAutoIncrement(bool $autoIncrement): void;

    /**
     * Returns type of the column.
     * @return string
     */
    public function getType(): string;
}
