<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

use function implode;
use function str_repeat;
use function str_replace;

final class PrimaryKeyRenderer implements PrimaryKeyRendererInterface
{
    /** @var string */
    private $addKeyTemplate = '$this->addPrimaryKey(\'{keyName}\', \'{tableName}\', [{keyColumns}]);';

    /** @var string */
    private $dropKeyTemplate = '$this->dropPrimaryKey(\'{keyName}\', \'{tableName}\');';

    public function renderUp(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string
    {
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->addKeyTemplate;

        $keyColumns = $primaryKey->getColumns();
        $renderedColumns = [];
        foreach ($keyColumns as $keyColumn) {
            $renderedColumns[] = "'$keyColumn'";
        }

        return str_replace(
            [
                '{keyName}',
                '{tableName}',
                '{keyColumns}',
            ],
            [
                $primaryKey->getName(),
                $tableName,
                implode(', ', $renderedColumns),
            ],
            $template
        );
    }

    public function renderDown(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string
    {
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->dropKeyTemplate;

        return str_replace(
            [
                '{keyName}',
                '{tableName}'
            ],
            [
                $primaryKey->getName(),
                $tableName
            ],
            $template
        );
    }
}
