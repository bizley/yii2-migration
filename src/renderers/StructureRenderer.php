<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\StructureInterface;

use function array_filter;
use function count;
use function explode;
use function implode;
use function mb_strlen;
use function mb_substr;
use function str_repeat;
use function str_replace;
use function strpos;

final class StructureRenderer implements StructureRendererInterface
{
    /** @var string|null */
    private $template = <<<'TEMPLATE'
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
     * Renders table name.
     * @param string|null $tableName
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderName(?string $tableName, bool $usePrefix, string $dbPrefix = null): ?string
    {
        if ($usePrefix === false) {
            return $tableName;
        }

        if ($dbPrefix !== null && strpos($tableName, $dbPrefix) === 0) {
            $tableName = mb_substr($tableName, mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    /**
     * Renders the migration structure.
     * @param StructureInterface $structure
     * @param int $indent
     * @param string $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string
     */
    public function renderStructure(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string {
        $renderedStructure = array_filter(
            [
                $this->renderStructureTable($structure, $indent, $schema, $engineVersion, $usePrefix, $dbPrefix),
                $this->renderStructurePrimaryKey($structure, $indent, $usePrefix, $dbPrefix),
                $this->renderStructureIndexes($structure, $indent, $usePrefix, $dbPrefix),
                $this->renderStructureForeignKeys($structure, $indent, $usePrefix, $dbPrefix)
            ]
        );

        return implode("\n\n", $renderedStructure);
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
     * Renders the table.
     * @param StructureInterface $structure
     * @param string $schema
     * @param string|null $engineVersion
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @param int $indent
     * @return string|null
     */
    private function renderStructureTable(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $template = $this->applyIndent($indent, $this->template);

        $columns = $structure->getColumns();
        $renderedColumns = [];
        foreach ($columns as $column) {
            $renderedColumns[] = $this->columnRenderer->render(
                $column,
                $structure->getPrimaryKey(),
                $indent + 8,
                $schema,
                $engineVersion
            );
        }

        return str_replace(
            [
                '{tableName}',
                '{columns}',
            ],
            [
                $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
                implode("\n", $renderedColumns),
            ],
            $template
        );
    }

    private function renderStructurePrimaryKey(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->primaryKeyRenderer->render(
            $structure->getPrimaryKey(),
            $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
            $indent
        );
    }

    private function renderStructureIndexes(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $indexes = $structure->getIndexes();
        $foreignKeys = $structure->getForeignKeys();

        $renderedIndexes = [];
        /** @var IndexInterface $index */
        foreach ($indexes as $index) {
            /** @var ForeignKeyInterface $foreignKey */
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getName() === $index->getName()) {
                    continue 2;
                }
            }

            $renderedIndexes[] = $this->indexRenderer->render(
                $index,
                $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    private function renderStructureForeignKeys(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->renderForeignKeys($structure, $structure->getForeignKeys(), $indent, $usePrefix, $dbPrefix);
    }

    /**
     * @param StructureInterface $structure
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeys(
        StructureInterface $structure,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedForeignKeys = [];
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->render(
                $foreignKey,
                $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
                $this->renderName($foreignKey->getReferencedTable(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * @param string|null $template
     */
    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }
}
