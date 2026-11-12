<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\Schema;
use bizley\migration\table\PrimaryKeyInterface;
use yii\base\NotSupportedException;

final class PrimaryKeyRenderer implements PrimaryKeyRendererInterface
{
    private string $addKeyTemplate = '$this->addPrimaryKey(\'{keyName}\', \'{tableName}\', [{keyColumns}]);';

    private string $dropKeyTemplate = '$this->dropPrimaryKey(\'{keyName}\', \'{tableName}\');';

    public function __construct(private readonly bool $generalSchema)
    {
    }

    /**
     * Renders the add primary key statement.
     * @throws NotSupportedException
     */
    public function renderUp(
        ?PrimaryKeyInterface $primaryKey,
        string $tableName,
        int $indent = 0,
        ?string $schema = null
    ): ?string {
        if ($primaryKey === null || !$primaryKey->isComposite()) {
            return null;
        }

        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
        }

        $template = \str_repeat(' ', $indent) . $this->addKeyTemplate;

        return \strtr(
            $template,
            [
                '{keyName}' => QuoteEscaper::escape($primaryKey->getName()),
                '{tableName}' => QuoteEscaper::escape($tableName),
                '{keyColumns}' => QuoteEscaper::renderList($primaryKey->getColumns()),
            ]
        );
    }

    /**
     * Renders the drop primary key statement.
     * @throws NotSupportedException
     */
    public function renderDown(
        ?PrimaryKeyInterface $primaryKey,
        string $tableName,
        int $indent = 0,
        ?string $schema = null
    ): ?string {
        if ($primaryKey === null || !$primaryKey->isComposite()) {
            return null;
        }

        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
        }

        $template = \str_repeat(' ', $indent) . $this->dropKeyTemplate;

        return \strtr(
            $template,
            [
                '{keyName}' => QuoteEscaper::escape($primaryKey->getName()),
                '{tableName}' => QuoteEscaper::escape($tableName),
            ]
        );
    }
}
