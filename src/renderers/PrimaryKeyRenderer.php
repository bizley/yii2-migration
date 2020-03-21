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
    private $template = '$this->addPrimaryKey(\'{keyName}\', \'{tableName}\', [{keyColumns}]);';

    public function renderUp(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string
    {
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->template;

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

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
