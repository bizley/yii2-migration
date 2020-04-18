<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\table\Blueprint;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\UpdaterInterface;
use yii\base\NotSupportedException;

final class UpdaterStub implements UpdaterInterface
{
    /** @var Blueprint */
    public static $blueprint;

    /** @var bool */
    public static $throwForPrepare = false;

    /** @var bool */
    public static $throwForGenerate = false;

    public function __construct()
    {
    }

    public function prepareBlueprint(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): BlueprintInterface {
        if (static::$throwForPrepare) {
            throw new NotSupportedException('Stub Exception');
        }
        return static::$blueprint;
    }

    public function generateFromBlueprint(
        BlueprintInterface $blueprint,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        if (static::$throwForGenerate) {
            throw new NotSupportedException('Stub Exception');
        }
        return '';
    }
}
