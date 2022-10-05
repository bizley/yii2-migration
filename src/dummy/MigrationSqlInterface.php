<?php

namespace bizley\migration\dummy;

use yii\db\MigrationInterface;

interface MigrationSqlInterface extends MigrationInterface
{
    /** @return string[] */
    public function getStatements(): array;
}
