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
    public function renderStructureUp(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string {
        $renderedStructure = array_filter(
            [
                $this->renderStructureTableUp($structure, $indent, $schema, $engineVersion, $usePrefix, $dbPrefix),
                $this->renderStructurePrimaryKeyUp($structure, $indent, $usePrefix, $dbPrefix),
                $this->renderStructureIndexesUp($structure, $indent, $usePrefix, $dbPrefix),
                $this->renderStructureForeignKeysUp($structure, $indent, $usePrefix, $dbPrefix)
            ]
        );

        return implode("\n\n", $renderedStructure);
    }

    public function renderStructureDown(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string {
        return $this->renderStructureTableDown($structure, $indent, $usePrefix, $dbPrefix);
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
    private function renderStructureTableUp(
        StructureInterface $structure,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $template = $this->applyIndent($indent, $this->createTableTemplate);

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

    private function renderStructureTableDown(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $template = $this->applyIndent($indent, $this->dropTableTemplate);

        return str_replace(
            ['{tableName}'],
            [$this->renderName($structure->getName(), $usePrefix, $dbPrefix)],
            $template
        );
    }

    private function renderStructurePrimaryKeyUp(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->primaryKeyRenderer->renderUp(
            $structure->getPrimaryKey(),
            $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
            $indent
        );
    }

    private function renderStructureIndexesUp(
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

            $renderedIndexes[] = $this->indexRenderer->renderUp(
                $index,
                $this->renderName($structure->getName(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    private function renderStructureForeignKeysUp(
        StructureInterface $structure,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        return $this->renderForeignKeysUp(
            $structure->getName(),
            $structure->getForeignKeys(),
            $indent,
            $usePrefix,
            $dbPrefix
        );
    }

    /**
     * @param string $structureName
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeysUp(
        string $structureName,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedForeignKeys = [];
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderUp(
                $foreignKey,
                $this->renderName($structureName, $usePrefix, $dbPrefix),
                $this->renderName($foreignKey->getReferencedTable(), $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * @param string $structureName
     * @param array $foreignKeys
     * @param int $indent
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @return string|null
     */
    public function renderForeignKeysDown(
        string $structureName,
        array $foreignKeys,
        int $indent = 0,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): ?string {
        $renderedForeignKeys = [];
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $renderedForeignKeys[] = $this->foreignKeyRenderer->renderDown(
                $foreignKey,
                $this->renderName($structureName, $usePrefix, $dbPrefix),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    /** @param string|null $createTableTemplate */
    public function setCreateTableTemplate(?string $createTableTemplate): void
    {
        $this->createTableTemplate = $createTableTemplate;
    }

    /** @param string $dropTableTemplate */
    public function setDropTableTemplate(string $dropTableTemplate): void
    {
        $this->dropTableTemplate = $dropTableTemplate;
    }
}
