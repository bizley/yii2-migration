<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\GeneratorInterface;
use Exception;

final class GeneratorStub implements GeneratorInterface
{
    /** @var bool */
    public static $throwForTable = false;

    /** @var bool */
    public static $throwForKeys = false;

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
        if (self::$throwForTable) {
            throw new Exception('Stub exception');
        }
        return '';
    }

    public function generateForForeignKeys(
        array $foreignKeys,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        if (self::$throwForKeys) {
            throw new Exception('Stub exception');
        }
        return '';
    }

    public function getSuppressedForeignKeys(): array
    {
        return ['test'];
    }
}
