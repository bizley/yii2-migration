<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\StructureInterface;

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

    public function __construct(StructureInterface $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Renders table name.
     * @return string
     */
    public function renderName(): string
    {
        $tableName = $this->structure->getName();

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
     * @return string
     */
    public function renderTable(int $indent = 0): string
    {
        $template = $this->applyIndent($indent, $this->getCreateTableTemplate());

        $columns = $this->structure->getColumns();
        $renderedColumns = [];
        foreach ($columns as $column) {
            $columnRenderer = new ColumnRenderer($column);
            $renderedColumns[] = $columnRenderer->render($indent + 8);
        }

        return str_replace(
            ['{tableName}', '{columns}'],
            [$this->renderName(), implode("\n", $renderedColumns)],
            $template
        );
    }

    public function renderPrimaryKey(int $indent = 0): string
    {
        $primaryKeyRenderer = new PrimaryKeyRenderer($this->structure->getPrimaryKey());
        return $primaryKeyRenderer->render($indent);
    }

    public function renderIndexes(int $indent = 0): string
    {
        $indexes = $this->structure->getIndexes();
        $renderedIndexes = [];
        foreach ($indexes as $index) {
            $indexRenderer = new IndexRenderer($index);
            $renderedIndexes[] = $indexRenderer->render($indent);
        }

        return implode("\n", $renderedIndexes);
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
}
