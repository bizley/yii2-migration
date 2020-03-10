<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

use function implode;
use function str_repeat;
use function str_replace;

class PrimaryKeyRenderer
{
    /**
     * @var PrimaryKeyInterface
     */
    private $primaryKey;

    /**
     * @var string
     */
    private $template = '$this->addPrimaryKey(\'{keyName}\', \'{tableName}\', [{keyColumns}]);';

    public function render(string $tableName, int $indent = 0): ?string
    {
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->getTemplate();

        $keyColumns = $primaryKey->getColumns();
        $renderedColumns = [];
        foreach ($keyColumns as $keyColumn) {
            $renderedColumns[] = "'$keyColumn'";
        }

        return str_replace(
            ['{keyName}', '{tableName}', '{keyColumns}'],
            [$primaryKey->getName(), $tableName, implode(', ', $renderedColumns)],
            $template
        );
    }

    /**
     * @return PrimaryKeyInterface
     */
    public function getPrimaryKey(): PrimaryKeyInterface
    {
        return $this->primaryKey;
    }

    /**
     * @param PrimaryKeyInterface $primaryKey
     */
    public function setPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
