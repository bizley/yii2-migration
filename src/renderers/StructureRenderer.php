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
    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var bool
     */
    private $usePrefix = true;

    /**
     * @var string|null
     */
    private $dbPrefix;

    /**
     * @var string|null
     */
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

    /**
     * @var ColumnRendererInterface
     */
    private $columnRenderer;

    /**
     * @var PrimaryKeyRendererInterface
     */
    private $primaryKeyRenderer;

    /**
     * @var IndexRendererInterface
     */
    private $indexRenderer;

    /**
     * @var ForeignKeyRendererInterface
     */
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
     * @return string|null
     */
    public function renderName(?string $tableName): ?string
    {
        if ($this->usePrefix === false) {
            return $tableName;
        }

        $dbPrefix = $this->dbPrefix;
        if ($dbPrefix !== null && strpos($tableName, $dbPrefix) === 0) {
            $tableName = mb_substr($tableName, mb_strlen($dbPrefix, 'UTF-8'), null, 'UTF-8');
        }

        return "{{%$tableName}}";
    }

    /**
     * Renders the migration structure.
     * @param string $schema
     * @param string|null $engineVersion
     * @param bool $generalSchema
     * @param int $indent
     * @return string
     */
    public function renderStructure(
        string $schema,
        string $engineVersion = null,
        bool $generalSchema = true,
        int $indent = 0
    ): string {
        $renderedStructure = array_filter(
            [
                $this->renderStructureTable($schema, $engineVersion, $generalSchema, $indent),
                $this->renderStructurePrimaryKey($indent),
                $this->renderStructureIndexes($indent),
                $this->renderStructureForeignKeys($indent)
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
     * @param string $schema
     * @param string|null $engineVersion
     * @param bool $generalSchema
     * @param int $indent
     * @return string|null
     */
    private function renderStructureTable(
        string $schema,
        string $engineVersion = null,
        bool $generalSchema = true,
        int $indent = 0
    ): ?string {
        if ($this->structure === null) {
            return null;
        }

        $template = $this->applyIndent($indent, $this->template);

        $columns = $this->structure->getColumns();
        $renderedColumns = [];
        foreach ($columns as $column) {
            $this->columnRenderer->setColumn($column);
            $renderedColumns[] = $this->columnRenderer->render($schema, $engineVersion, $generalSchema, $indent + 8);
        }

        return str_replace(
            ['{tableName}', '{columns}'],
            [$this->renderName($this->structure->getName()), implode("\n", $renderedColumns)],
            $template
        );
    }

    private function renderStructurePrimaryKey(int $indent = 0): ?string
    {
        if ($this->structure === null) {
            return null;
        }

        $this->primaryKeyRenderer->setPrimaryKey($this->structure->getPrimaryKey());
        return $this->primaryKeyRenderer->render($this->renderName($this->structure->getName()), $indent);
    }

    private function renderStructureIndexes(int $indent = 0): ?string
    {
        if ($this->structure === null) {
            return null;
        }

        $indexes = $this->structure->getIndexes();
        $foreignKeys = $this->structure->getForeignKeys();

        $renderedIndexes = [];
        /** @var IndexInterface $index */
        foreach ($indexes as $index) {
            /** @var ForeignKeyInterface $foreignKey */
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getName() === $index->getName()) {
                    continue 2;
                }
            }

            $this->indexRenderer->setIndex($index);
            $renderedIndexes[] = $this->indexRenderer->render($this->renderName($this->structure->getName()), $indent);
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    private function renderStructureForeignKeys(int $indent = 0): ?string
    {
        if ($this->structure === null) {
            return null;
        }

        return $this->renderForeignKeys($this->structure->getForeignKeys(), $indent);
    }

    /**
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param int $indent
     * @return string|null
     */
    public function renderForeignKeys(array $foreignKeys, int $indent = 0): ?string
    {
        $renderedForeignKeys = [];
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $this->foreignKeyRenderer->setForeignKey($foreignKey);
            $renderedForeignKeys[] = $this->foreignKeyRenderer->render(
                $this->renderName($this->structure->getName()),
                $this->renderName($foreignKey->getReferencedTable()),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * @param bool $usePrefix
     */
    public function setUsePrefix(bool $usePrefix): void
    {
        $this->usePrefix = $usePrefix;
    }

    /**
     * @param string|null $dbPrefix
     */
    public function setDbPrefix(?string $dbPrefix): void
    {
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * @param string|null $template
     */
    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    /**
     * @param StructureInterface $structure
     */
    public function setStructure(StructureInterface $structure): void
    {
        $this->structure = $structure;
    }
}
