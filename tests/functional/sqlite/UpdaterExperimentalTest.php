<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use PHPUnit\Framework\Attributes\Group;

#[Group('sqlite')]
final class UpdaterExperimentalTest extends \bizley\tests\functional\UpdaterExperimentalTestCase
{
    /** @var string */
    public static $schema = 'sqlite';
}
