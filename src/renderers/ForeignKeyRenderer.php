<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

use function is_numeric;

class ForeignKeyRenderer implements ForeignKeyRendererInterface
{
    /**
     * @var ForeignKeyInterface
     */
    private $foreignKey;

    /**
     * @var string
     */
    private $template = <<<'TEMPLATE'
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

    /**
     * @var string
     */
    private $keyNameTemplate = 'fk-{tableName}-{keyColumns}';

    public function render(string $tableName, string $referencedTableName, int $indent = 0): ?string
    {
        if ($this->foreignKey === null) {
            return null;
        }

        $template = $this->applyIndent($indent, $this->template);

        $keyColumns = $this->foreignKey->getColumns();
        $renderedKeyColumns = [];
        foreach ($keyColumns as $keyColumn) {
            $renderedKeyColumns[] = "'$keyColumn'";
        }

        $referencedColumns = $this->foreignKey->getReferencedColumns();
        $renderedReferencedColumns = [];
        foreach ($referencedColumns as $referencedColumn) {
            $renderedReferencedColumns[] = "'$referencedColumn'";
        }

        $onDelete = $this->foreignKey->getOnDelete();
        $onUpdate = $this->foreignKey->getOnUpdate();

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
                $this->renderName($tableName),
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

    private function applyIndent(int $indent, ?string $template): ?string
    {
        if ($indent < 1 || $template === null) {
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
     * @param string $table
     * @return string
     */
    private function renderName(string $table): string
    {
        $name = $this->foreignKey->getName();

        if ($name !== null && is_numeric($name) === false) {
            return $name;
        }

        return str_replace(
            ['{tableName}', '{keyColumns}'],
            [$table, implode('-', $this->foreignKey->getColumns())],
            $this->keyNameTemplate
        );
    }

    /**
     * @param ForeignKeyInterface $foreignKey
     */
    public function setForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /**
     * @param string $keyNameTemplate
     */
    public function setKeyNameTemplate(string $keyNameTemplate): void
    {
        $this->keyNameTemplate = $keyNameTemplate;
    }
}
