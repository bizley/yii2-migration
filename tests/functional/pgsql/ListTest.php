<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use PHPUnit\Framework\Attributes\Group;

#[Group('pgsql')]
final class ListTest extends \bizley\tests\functional\ListTestCase
{
    /** @var string */
    public static $schema = 'pgsql';
}
