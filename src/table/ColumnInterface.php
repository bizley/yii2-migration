<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ColumnInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function isPrimaryKey(): bool;

    public function setPrimaryKey(bool $primaryKey): void;

    public function isColumnInPrimaryKey(PrimaryKeyInterface $primaryKey): bool;

    public function isPrimaryKeyInfoAppended(string $schema): bool;

    public function getAppend(): ?string;

    public function setAppend(?string $append): void;

    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement, string $schema = null): ?string;

    public function removeAppendedPrimaryKeyInfo(string $schema): ?string;

    public function setComment(?string $comment): void;

    public function getComment(): ?string;

    public function getSize(): ?int;

    /** @param int|string|null $size */
    public function setSize($size): void;

    public function getPrecision(): ?int;

    /** @param int|string|null $precision */
    public function setPrecision($precision): void;

    public function getScale(): ?int;

    /** @param int|string|null $scale */
    public function setScale($scale): void;

    public function isNotNull(): ?bool;

    public function setNotNull(?bool $notNull): void;

    /** @return mixed */
    public function getDefault();

    /** @param mixed $default */
    public function setDefault($default): void;

    public function isUnsigned(): bool;

    public function setUnsigned(bool $unsigned): void;

    public function isUnique(): bool;

    public function setUnique(bool $unique): void;

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null);

    /**
     * @param string|int|array<string|int> $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void;

    public function getDefinition(): string;

    public function getAfter(): ?string;

    public function setAfter(?string $after): void;

    public function isFirst(): bool;

    public function setFirst(bool $first): void;

    public function isAutoIncrement(): bool;

    public function setAutoIncrement(bool $autoIncrement): void;

    public function getDefaultMapping(): ?string;

    public function getType(): string;
}
