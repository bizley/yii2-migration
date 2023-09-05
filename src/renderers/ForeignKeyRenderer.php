<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\Schema;
use bizley\migration\table\ForeignKeyInterface;
use yii\base\NotSupportedException;

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

    /** @var bool */
    private $generalSchema;

    public function __construct(bool $generalSchema)
    {
        $this->generalSchema = $generalSchema;
    }

    /**
     * Renders the add foreign key statement.
     * @param ForeignKeyInterface $foreignKey
     * @param string $tableName
     * @param string $referencedTableName
     * @param int $indent
     * @param string|null $schema
     * @return string
     * @throws NotSupportedException
     */
    public function renderUp(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0,
        string $schema = null
    ): string {
        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
        }

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

        return \str_replace(
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
                $foreignKey->getName(),
                $tableName,
                \implode(', ', $renderedKeyColumns),
                $referencedTableName,
                \implode(', ', $renderedReferencedColumns),
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
     * @param string|null $schema
     * @return string
     * @throws NotSupportedException
     */
    public function renderDown(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        int $indent = 0,
        string $schema = null
    ): string {
        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
        }

        $template = $this->applyIndent($indent, $this->dropKeyTemplate);

        return \str_replace(
            [
                '{keyName}',
                '{tableName}'
            ],
            [
                $foreignKey->getName(),
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

        $rows = \explode("\n", $template);
        foreach ($rows as &$row) {
            if ($row !== '') {
                $row = \str_repeat(' ', $indent) . $row;
            }
        }

        return \implode("\n", $rows);
    }
}
