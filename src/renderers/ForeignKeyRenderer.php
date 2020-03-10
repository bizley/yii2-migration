<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

use function is_numeric;

class ForeignKeyRenderer
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
        $foreignKey = $this->getForeignKey();
        if ($foreignKey === null) {
            return null;
        }

        $template = str_repeat(' ', $indent) . $this->getTemplate();

        $keyColumns = $foreignKey->getColumns();
        $renderedKeyColumns = [];
        foreach ($keyColumns as $keyColumn) {
            $renderedKeyColumns[] = "'$keyColumn'";
        }

        $referencedColumns = $foreignKey->getReferencedColumns();
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
     * Renders key name.
     * @param ForeignKeyInterface $foreignKey
     * @param string $table
     * @return string
     */
    public function renderName(ForeignKeyInterface $foreignKey, string $table): string
    {
        $name = $foreignKey->getName();

        if ($name !== null && is_numeric($name) === false) {
            return $name;
        }

        $template = $this->getKeyNameTemplate();

        return str_replace(
            ['{tableName}', '{keyColumns}'],
            [$table, implode('-', $foreignKey->getColumns())],
            $template
        );
    }

    /**
     * @return ForeignKeyInterface
     */
    public function getForeignKey(): ForeignKeyInterface
    {
        return $this->foreignKey;
    }

    /**
     * @param ForeignKeyInterface $foreignKey
     */
    public function setForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
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

    /**
     * @return string
     */
    public function getKeyNameTemplate(): string
    {
        return $this->keyNameTemplate;
    }

    /**
     * @param string $keyNameTemplate
     */
    public function setKeyNameTemplate(string $keyNameTemplate): void
    {
        $this->keyNameTemplate = $keyNameTemplate;
    }
}
