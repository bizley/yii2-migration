<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use PHPUnit\Framework\Attributes\Group;

#[Group('pgsql')]
final class UpdaterExperimentalTest extends \bizley\tests\functional\UpdaterExperimentalTestCase
{
    /** @var string */
    public static $schema = 'pgsql';
}
