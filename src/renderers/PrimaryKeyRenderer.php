<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\Schema;
use bizley\migration\table\PrimaryKeyInterface;
use yii\base\NotSupportedException;

use function implode;
use function str_repeat;
use function str_replace;

final class PrimaryKeyRenderer implements PrimaryKeyRendererInterface
{
    /** @var string */
    private $addKeyTemplate = '$this->addPrimaryKey(\'{keyName}\', \'{tableName}\', [{keyColumns}]);';

    /** @var string */
    private $dropKeyTemplate = '$this->dropPrimaryKey(\'{keyName}\', \'{tableName}\');';

    /** @var bool */
    private $generalSchema;

    public function __construct(bool $generalSchema)
    {
        $this->generalSchema = $generalSchema;
    }

    /**
     * Renders the add primary key statement.
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string $tableName
     * @param int $indent
     * @param string|null $schema
     * @return string|null
     * @throws NotSupportedException
     */
    public function renderUp(
        ?PrimaryKeyInterface $primaryKey,
        string $tableName,
        int $indent = 0,
        string $schema = null
    ): ?string {
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
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

    /**
     * Renders the drop primary key statement.
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string $tableName
     * @param int $indent
     * @param string|null $schema
     * @return string|null
     * @throws NotSupportedException
     */
    public function renderDown(
        ?PrimaryKeyInterface $primaryKey,
        string $tableName,
        int $indent = 0,
        string $schema = null
    ): ?string {
        if ($primaryKey === null || $primaryKey->isComposite() === false) {
            return null;
        }

        if ($schema === Schema::SQLITE && $this->generalSchema === false) {
            throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
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
