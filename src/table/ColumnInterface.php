<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ColumnInterface
{
    /**
     * Returns name of the column.
     */
    public function getName(): string;

    /**
     * Sets name for the column.
     */
    public function setName(string $name): void;

    /**
     * Checks whether the column is a primary key.
     */
    public function isPrimaryKey(): bool;

    /**
     * Sets the primary key flag for the column.
     */
    public function setPrimaryKey(?bool $primaryKey): void;

    /**
     * Checks if column is a part of the primary key.
     */
    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool;

    /**
     * Checks if information of primary key is set in append statement.
     */
    public function isPrimaryKeyInfoAppended(?string $schema): bool;

    /**
     * Returns the value of append statement of the column.
     */
    public function getAppend(): ?string;

    /**
     * Sets the value for append statement for the column.
     */
    public function setAppend(?string $append): void;

    /**
     * Prepares append statement based on the schema.
     * @param bool $primaryKey whether the column is primary key
     * @param bool $autoIncrement whether the column has autoincrement flag
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement, ?string $schema = null): ?string;

    /**
     * Removes information of primary key in append statement and returns what is left.
     */
    public function removeAppendedPrimaryKeyInfo(?string $schema): ?string;

    /**
     * Sets the value for comment statement for the column.
     */
    public function setComment(?string $comment): void;

    /**
     * Returns the value for comment statement for the column.
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
     */
    public function isNotNull(): ?bool;

    /**
     * Sets the column to not be null.
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
     */
    public function isUnsigned(): bool;

    /**
     * Sets the unsigned flag for the column.
     */
    public function setUnsigned(bool $unsigned): void;

    /**
     * Checks whether the column is unique.
     */
    public function isUnique(): bool;

    /**
     * Sets the uniqueness of the column.
     */
    public function setUnique(bool $unique): void;

    /**
     * Returns length of the column.
     * @return string|int|null
     */
    public function getLength(?string $schema = null, ?string $engineVersion = null);

    /**
     * Sets length for the column.
     * @param string|int|array<string|int>|null $value
     */
    public function setLength($value, ?string $schema = null, ?string $engineVersion = null): void;

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string;

    /**
     * Returns the value for after statement for the column.
     */
    public function getAfter(): ?string;

    /**
     * Sets the value for after statement for the column.
     */
    public function setAfter(?string $after): void;

    /**
     * Checks whether the column has first statement.
     */
    public function isFirst(): bool;

    /**
     * Sets the column for the first statement.
     */
    public function setFirst(bool $first): void;

    /**
     * Checks whether the column has autoincrement flag.
     */
    public function isAutoIncrement(): bool;

    /**
     * Sets the autoincrement flag for the column.
     */
    public function setAutoIncrement(bool $autoIncrement): void;

    /**
     * Returns type of the column.
     */
    public function getType(): string;
}
