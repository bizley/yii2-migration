<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\StructureInterface;

use function count;
use function explode;
use function implode;
use function mb_strlen;
use function str_repeat;
use function str_replace;
use function strpos;
use function substr;

class StructureRenderer
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

    /**
     * @var ColumnRenderer
     */
    private $columnRenderer;

    /**
     * @var PrimaryKeyRenderer
     */
    private $primaryKeyRenderer;

    /**
     * @var IndexRenderer
     */
    private $indexRenderer;

    /**
     * @var ForeignKeyRenderer
     */
    private $foreignKeyRenderer;

    public function __construct(
        ColumnRenderer $columnRenderer,
        PrimaryKeyRenderer $primaryKeyRenderer,
        IndexRenderer $indexRenderer,
        ForeignKeyRenderer $foreignKeyRenderer
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
        if ($this->isUsePrefix() === false) {
            return $tableName;
        }

        $dbPrefix = $this->getDbPrefix();
        if ($dbPrefix !== null && strpos($tableName, $dbPrefix) === 0) {
            $tableName = substr($tableName, mb_strlen($dbPrefix, 'UTF-8'));
        }

        return "{{%$tableName}}";
    }

    /**
     * Renders the migration structure.
     * @param int $indent
     * @return string
     */
    public function render(int $indent = 0): string
    {
        return $this->renderTable($indent)
            . $this->renderPrimaryKey($indent)
            . $this->renderIndexes($indent)
            . $this->renderForeignKeys($indent)
            . "\n";
    }

    private function applyIndent(int $indent, string $template): string
    {
        if ($indent < 1) {
            return $template;
        }

        $rows = explode("\n", $template);
        foreach ($rows as &$row) {
            $row = str_repeat(' ', $indent) . $row;
        }

        return implode("\n", $rows);
    }

    /**
     * Renders the table.
     * @param int $indent
     * @return string|null
     */
    public function renderTable(int $indent = 0): ?string
    {
        $structure = $this->getStructure();
        if ($structure === null) {
            return null;
        }

        $template = $this->applyIndent($indent, $this->getCreateTableTemplate());

        $columns = $structure->getColumns();
        $renderedColumns = [];
        foreach ($columns as $column) {
            $this->columnRenderer->setColumn($column);
            $renderedColumns[] = $this->columnRenderer->render($indent + 8);
        }

        return str_replace(
            ['{tableName}', '{columns}'],
            [$this->renderName($structure->getName()), implode("\n", $renderedColumns)],
            $template
        );
    }

    public function renderPrimaryKey(int $indent = 0): ?string
    {
        $structure = $this->getStructure();
        if ($structure === null) {
            return null;
        }

        $this->primaryKeyRenderer->setPrimaryKey($structure->getPrimaryKey());
        return $this->primaryKeyRenderer->render($this->renderName($structure->getName()), $indent);
    }

    public function renderIndexes(int $indent = 0): ?string
    {
        $structure = $this->getStructure();
        if ($structure === null) {
            return null;
        }

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

            $this->indexRenderer->setIndex($index);
            $renderedIndexes[] = $this->indexRenderer->render($this->renderName($structure->getName()), $indent);
        }

        return count($renderedIndexes) ? implode("\n", $renderedIndexes) : null;
    }

    public function renderForeignKeys(int $indent = 0): ?string
    {
        $structure = $this->getStructure();
        if ($structure === null) {
            return null;
        }

        $foreignKeys = $structure->getForeignKeys();
        $renderedForeignKeys = [];
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKey) {
            $this->foreignKeyRenderer->setForeignKey($foreignKey);
            $renderedForeignKeys[] = $this->foreignKeyRenderer->render(
                $this->renderName($structure->getName()),
                $this->renderName($foreignKey->getReferencedTable()),
                $indent
            );
        }

        return count($renderedForeignKeys) ? implode("\n", $renderedForeignKeys) : null;
    }

    /**
     * @return bool
     */
    public function isUsePrefix(): bool
    {
        return $this->usePrefix;
    }

    /**
     * @param bool $usePrefix
     */
    public function setUsePrefix(bool $usePrefix): void
    {
        $this->usePrefix = $usePrefix;
    }

    /**
     * @return string|null
     */
    public function getDbPrefix(): ?string
    {
        return $this->dbPrefix;
    }

    /**
     * @param string|null $dbPrefix
     */
    public function setDbPrefix(?string $dbPrefix): void
    {
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * @return string|null
     */
    public function getCreateTableTemplate(): ?string
    {
        return $this->createTableTemplate;
    }

    /**
     * @param string|null $createTableTemplate
     */
    public function setCreateTableTemplate(?string $createTableTemplate): void
    {
        $this->createTableTemplate = $createTableTemplate;
    }

    /**
     * @return StructureInterface
     */
    public function getStructure(): StructureInterface
    {
        return $this->structure;
    }

    /**
     * @param StructureInterface $structure
     */
    public function setStructure(StructureInterface $structure): void
    {
        $this->structure = $structure;
    }
}
