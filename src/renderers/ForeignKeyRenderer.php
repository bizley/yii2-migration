<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

use function explode;
use function implode;
use function is_numeric;
use function str_repeat;
use function str_replace;

final class ForeignKeyRenderer implements ForeignKeyRendererInterface
{
    /** @var string */
    private $addKeyTemplate = <<<'TEMPLATE'
$this->addForeignKey(
    '{keyName}',
    '{tableName}',
    [{keyColumns}],
    '{referencedTableName}',
    [{referencedColumns}],
    {onDelete},
    {onUpdate}
);
TEMPLATE;

    /** @var string */
    private $dropKeyTemplate = '$this->dropForeignKey(\'{keyName}\', \'{tableName}\');';

    /** @var string */
    private $keyNameTemplate = 'fk-{tableName}-{keyColumns}';

    /**
     * Renders the add foreign key statement.
     * @param ForeignKeyInterface $foreignKey
     * @param string $tableName
     * @param string $referencedTableName
     * @param int $indent
     * @return string
     */
    public function renderUp(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0
    ): string {
        $template = $this->applyIndent($indent, $this->addKeyTemplate);

        $keyColumns = $foreignKey->getColumns();
        $renderedKeyColumns = [];
        foreach ($keyColumns as $keyColumn) {
            $renderedKeyColumns[] = "'$keyColumn'";
        }

        $referencedColumns = $foreignKey->getReferredColumns();
        $renderedReferencedColumns = [];
        foreach ($referencedColumns as $referencedColumn) {
            $renderedReferencedColumns[] = "'$referencedColumn'";
        }

        $onDelete = $foreignKey->getOnDelete();
        $onUpdate = $foreignKey->getOnUpdate();

        return str_replace(
            [
                '{keyName}',
                '{tableName}',
                '{keyColumns}',
                '{referencedTableName}',
                '{referencedColumns}',
                '{onDelete}',
                '{onUpdate}',
            ],
            [
                $this->renderName($foreignKey, $tableName),
                $tableName,
                implode(', ', $renderedKeyColumns),
                $referencedTableName,
                implode(', ', $renderedReferencedColumns),
                $onDelete ? "'$onDelete'" : 'null',
                $onUpdate ? "'$onUpdate'" : 'null'
            ],
            $template
        );
    }

    /**
     * Renders the drop foreign key statement.
     * @param ForeignKeyInterface $foreignKey
     * @param string $tableName
     * @param int $indent
     * @return string
     */
    public function renderDown(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        int $indent = 0
    ): string {
        $template = $this->applyIndent($indent, $this->dropKeyTemplate);

        return str_replace(
            [
                '{keyName}',
                '{tableName}'
            ],
            [
                $this->renderName($foreignKey, $tableName),
                $tableName
            ],
            $template
        );
    }

    /**
     * Applies the indent to every row in the template.
     * @param int $indent
     * @param string $template
     * @return string
     */
    private function applyIndent(int $indent, string $template): string
    {
        if ($indent < 1) {
            return $template;
        }

        $rows = explode("\n", $template);
        foreach ($rows as &$row) {
            if ($row !== '') {
                $row = str_repeat(' ', $indent) . $row;
            }
        }

        return implode("\n", $rows);
    }

    /**
     * Renders key name.
     * @param ForeignKeyInterface $foreignKey
     * @param string $table
     * @return string
     */
    private function renderName(ForeignKeyInterface $foreignKey, string $table): string
    {
        $name = $foreignKey->getName();

        if ($name !== null && is_numeric($name) === false) {
            return $name;
        }

        return str_replace(
            [
                '{tableName}',
                '{keyColumns}',
            ],
            [
                $table,
                implode('-', $foreignKey->getColumns()),
            ],
            $this->keyNameTemplate
        );
    }
}
