<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ColumnInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function isPrimaryKey(): bool;

    public function isPrimaryKeyInfoAppended(string $schema): bool;

    public function getAppend(): string;

    public function setAppend(?string $append): void;

    public function prepareSchemaAppend(string $schema, bool $primaryKey, bool $autoIncrement): ?string;

    public function removeAppendedPrimaryKeyInfo(string $schema): ?string;

    public function setComment(?string $comment): void;

    public function getComment(): string;

    public function getSize(): ?int;

    public function getPrecision(): ?int;

    public function getScale(): ?int;

    public function isNotNull(): ?bool;

    public function getDefault();

    public function isUnsigned(): bool;

    public function isUnique(): bool;

    public function getLength(string $schema = null, string $engineVersion = null);

    public function setLength($value, string $schema = null, string $engineVersion = null): void;

    public function getDefinition(): string;
}
