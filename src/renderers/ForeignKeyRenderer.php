<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\Schema;
use bizley\migration\table\ForeignKeyInterface;
use yii\base\NotSupportedException;

final class ForeignKeyRenderer implements ForeignKeyRendererInterface
{
    private string $addKeyTemplate = <<<'TEMPLATE'
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

    private string $dropKeyTemplate = '$this->dropForeignKey(\'{keyName}\', \'{tableName}\');';

    public function __construct(private readonly bool $generalSchema)
    {
    }

    /**
     * Renders the add foreign key statement.
     * @throws NotSupportedException
     */
    public function renderUp(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0,
        ?string $schema = null
    ): string {
        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
        }

        $template = $this->applyIndent($indent, $this->addKeyTemplate);

        $onDelete = $foreignKey->getOnDelete();
        $onUpdate = $foreignKey->getOnUpdate();

        return \strtr(
            $template,
            [
                '{keyName}' => QuoteEscaper::escape($foreignKey->getName()),
                '{tableName}' => QuoteEscaper::escape($tableName),
                '{keyColumns}' => QuoteEscaper::renderList($foreignKey->getColumns()),
                '{referencedTableName}' => QuoteEscaper::escape($referencedTableName),
                '{referencedColumns}' => QuoteEscaper::renderList($foreignKey->getReferredColumns()),
                '{onDelete}' => $onDelete ? "'" . QuoteEscaper::escape($onDelete) . "'" : 'null',
                '{onUpdate}' => $onUpdate ? "'" . QuoteEscaper::escape($onUpdate) . "'" : 'null',
            ]
        );
    }

    /**
     * Renders the drop foreign key statement.
     * @throws NotSupportedException
     */
    public function renderDown(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        int $indent = 0,
        ?string $schema = null
    ): string {
        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
        }

        $template = $this->applyIndent($indent, $this->dropKeyTemplate);

        return \strtr(
            $template,
            [
                '{keyName}' => QuoteEscaper::escape($foreignKey->getName()),
                '{tableName}' => QuoteEscaper::escape($tableName),
            ]
        );
    }

    /**
     * Applies the indent to every row in the template.
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
