<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;

final class StructureRenderer implements StructureRendererInterface
{
    /** @var string */
    private $createTableTemplate = <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{tableName}',
    [
{columns}
    ],
    $tableOptions
);
TEMPLATE;

    /** @var string */
    private $dropTableTemplate = '$this->dropTable(\'{tableName}\');';

    /** @var ColumnRendererInterface */
    private $columnRenderer;

    /** @var PrimaryKeyRendererInterface */
    private $primaryKeyRenderer;

    /** @var IndexRendererInterface */
    private $indexRenderer;

    /** @var ForeignKeyRendererInterface */
    private $foreignKeyRenderer;

    public function __construct(
        ColumnRendererInterface $columnRenderer,
        PrimaryKeyRendererInterface $primaryKeyRenderer,
        IndexRendererInterface $indexRenderer,
        ForeignKeyRendererInterface $foreignKeyRenderer
    ) {
        $this->columnRenderer = $columnRenderer;
        $this->primaryKeyRenderer = $primaryKeyRenderer;
        $this->indexRenderer = $indexRenderer;
        $this->foreignKeyRenderer = $foreignKeyRenderer;
    }

    /**
     * Renders table name. Name should be provided without the prefix. If name should be with prefix and it is being
     * detected, prefix is removed from the name and replaced by a prefix structure ({{%}}).
     */
    public function renderName(string $tableName, bool $usePrefix, ?string $dbPrefix = null): string
    {
        if ($usePrefix === false) {
            return $tableName;
        }

        if (!empty($dbPrefix) && \strpos($tableName, $dbPrefix) === 0) {
            $tableName = \mb_substr($tableName, \mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    /**
     * Renders the migration structure for up().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#up()-detail
     */
    public function renderStructureUp(
        StructureInterface $structure,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null,
        bool $usePrefix = true,
        ?string $dbPrefix = null
    ): string {
        $tableName = $this->renderName($structure->getName(), $usePrefix, $dbPrefix);

        $renderedStructure = \array_filter(
            [
                $this->renderStructureTableUp($structure, $tableName, $indent, $schema, $engineVersion),
                $this->renderStructurePrimaryKeyUp($structure, $tableName, $indent, $schema),
                $this->renderStructureIndexesUp($structure, $tableName, $indent),
                $this->renderStructureForeignKeysUp($structure, $indent, $usePrefix, $dbPrefix, $schema)
            ]
        );

        return \implode("\n\n", $renderedStructure);
    }

    /**
     * Renders the migration structure for down().
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration#down()-detail
     */
    public function renderStructureDown(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        ?string $dbPrefix = null
    ): string {
        return $this->renderStructureTableDown(
            $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
            $indent
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

    /**
     * Renders the create-table statement.
     */
    private function renderStructureTableUp(
        StructureInterface $structure,
        string $tableName,
        int $indent = 0,
        ?string $schema = null,
        ?string $engineVersion = null
    ): string {
        $columns = $structure->getColumns();
        $renderedColumns = [];
        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->render(
                $column,
                $structure->getPrimaryKey(),
                8,
                $schema,
                $engineVersion
            );
        }

        return $this->applyIndent(
            $indent,
            \str_replace(
                [
                    '{tableName}',
                    '{columns}',
                ],
                [
                    $tableName,
                    \implode("\n", $renderedColumns),
                ],
                $this->createTableTemplate
            )
        );
    }

    /**
     * Renders the drop-table statement.
     */
    private function renderStructureTableDown(
        string $tableName,
        int $indent = 0
    ): string {
        $template = $this->applyIndent($indent, $this->dropTableTemplate);

        return \str_replace('{tableName}', $tableName, $template);
    }

    /**
     * Renders the add-primary-key statement.
     */
    private function renderStructurePrimaryKeyUp(
        StructureInterface $structure,
        string $tableName,
        int $indent = 0,
        ?string $schema = null
    ): ?string {
        return $this->primaryKeyRenderer->renderUp($structure->getPrimaryKey(), $tableName, $indent, $schema);
    }

    /**
     * Renders the add indexes statements.
     */
    private function renderStructureIndexesUp(
        StructureInterface $structure,
        string $tableName,
        int $indent = 0
    ): ?string {
        $indexes = $structure->getIndexes();
        $foreignKeys = $structure->getForeignKeys();

        $renderedIndexes = [];

        foreach ($indexes as $index) {
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getName() === $index->getName()) {
                    continue 2;
                }
            }

            $renderedIndexes[] = $this->indexRenderer->renderUp(
                $index,
                $tableName,
                $indent
            );
        }

        return !empty($renderedIndexes) ? \implode("\n", $renderedIndexes) : null;
    }

    /**
     * Renders the add foreign keys statements (through the structure).
     */
    private function renderStructureForeignKeysUp(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        ?string $dbPrefix = null,
        ?string $schema = null
    ): ?string {
        return $this->renderForeignKeysUp(
            $structure->getForeignKeys(),
            $indent,
            $usePrefix,
            $dbPrefix,
            $schema
        );
    }

    /**
     * Renders the add foreign keys statements (direct).
     * @param array<ForeignKeyInterface> $foreignKeys
     */
    public function renderForeignKeysUp(
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        ?string $dbPrefix = null,
        ?string $schema = null
    ): ?string {
        $renderedForeignKeys = [];
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderUp(
                $foreignKey,
                $this->renderName($foreignKey->getTableName(), $usePrefix, $dbPrefix),
                $this->renderName($foreignKey->getReferredTable(), $usePrefix, $dbPrefix),
                $indent,
                $schema
            );
        }

        return !empty($renderedForeignKeys) ? \implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * Renders the drop foreign keys statements.
     * @param array<ForeignKeyInterface> $foreignKeys
     */
    public function renderForeignKeysDown(
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        ?string $dbPrefix = null,
        ?string $schema = null
    ): ?string {
        $renderedForeignKeys = [];
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderDown(
                $foreignKey,
                $this->renderName($foreignKey->getTableName(), $usePrefix, $dbPrefix),
                $indent,
                $schema
            );
        }

        return !empty($renderedForeignKeys) ? \implode("\n", $renderedForeignKeys) : null;
    }
}
