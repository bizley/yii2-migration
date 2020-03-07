<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ColumnInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function isPrimaryKey(): bool;

    public function isPrimaryKeyInfoAppended(): bool;

    public function getAppend(): string;

    public function setAppend(?string $append): void;

    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement): ?string;

    public function removeAppendedPrimaryKeyInfo(): ?string;

    public function setComment(?string $comment): void;
}
