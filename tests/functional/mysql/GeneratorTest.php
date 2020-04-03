<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

class GeneratorTest extends \bizley\tests\functional\GeneratorTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}
