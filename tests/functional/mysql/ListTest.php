<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

/** @group mysql */
final class ListTest extends \bizley\tests\functional\ListTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}
