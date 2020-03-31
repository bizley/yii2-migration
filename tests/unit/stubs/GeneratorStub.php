<?php

declare(strict_types=1);

namespace bizley\tests\unit\stubs;

use bizley\migration\GeneratorInterface;
use Exception;

final class GeneratorStub implements GeneratorInterface
{
    public function __construct()
    {
    }

    public function generateForTable(
        string $tableName,
        string $migrationName,
        array $referencesToPostpone = [],
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        return '';
    }

    public function generateForForeignKeys(
        array $foreignKeys,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        throw new Exception('Stub exception');
    }

    public function getSuppressedForeignKeys(): array
    {
        return ['test'];
    }
}
