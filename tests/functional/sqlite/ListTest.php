<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use PHPUnit\Framework\Attributes\Group;

#[Group('sqlite')]
final class ListTest extends \bizley\tests\functional\ListTestCase
{
    /** @var string */
    public static $schema = 'sqlite';
}
