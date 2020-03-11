<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\PrimaryKeyVariantColumnInterface;
use yii\db\Expression;
use yii\helpers\Json;

use function array_unshift;
use function is_array;
use function preg_match;
use function str_replace;
use function strpos;
use function trim;

class ColumnRenderer implements ColumnRendererInterface
{
    /**
     * @var ColumnInterface
     */
    private $column;

    /**
     * @var PrimaryKeyInterface
     */
    private $primaryKey;

    /**
     * @var array
     */
    private $definition = [];

    /**
     * @var bool
     */
    private $isUnsignedPossible = true;

    /**
     * @var bool
     */
    private $isNotNullPossible = true;

    /**
     * @var bool
     */
    protected $isPrimaryKeyPossible = true;

    public function render(
        string $schema,
        bool $generalSchema = true,
        string $engineVersion = null,
        int $indent = 0
    ): ?string {
        if ($this->column === null) {
            return null;
        }

        return str_repeat(' ', $indent)
            . "'{$this->column->getName()}' => {$this->renderDefinition($schema, $generalSchema, $engineVersion)},";
    }

    /**
     * Renders column definition.
     * @param string $schema
     * @param bool $generalSchema
     * @param string|null $engineVersion
     * @return string
     */
    private function renderDefinition(string $schema, bool $generalSchema = true, string $engineVersion = null): string
    {
        $this->definition = [];

        $this->buildColumnDefinition($schema, $generalSchema, $engineVersion);
        $this->buildGeneralDefinition($schema);

        return implode('->', $this->definition);
    }

    private function buildColumnDefinition(string $schema, bool $generalSchema, string $engineVersion = null): void
    {
        $definition = $this->column->getDefinition();

        if ($generalSchema) {
            if ($this->column instanceof PrimaryKeyColumnInterface) {
                $this->isPrimaryKeyPossible = false;
                $this->isNotNullPossible = false;
            } elseif (
                $this->column instanceof PrimaryKeyVariantColumnInterface
                && $this->primaryKey->isComposite() === false
                && $this->column->isColumnInPrimaryKey($this->primaryKey)
            ) {
                $this->isPrimaryKeyPossible = false;
                $this->isNotNullPossible = false;
                $definition = $this->column->getPrimaryKeyDefinition();
            }
        }

        $this->definition[] = str_replace(
            '{renderLength}',
            $this->getRenderedLength($schema, $generalSchema, $engineVersion),
            $definition
        );
    }

    /**
     * @param string $schema
     * @param bool $generalSchema
     * @param string|null $engineVersion
     * @return string|null
     */
    private function getRenderedLength(string $schema, bool $generalSchema, string $engineVersion = null): ?string
    {
        $length = $this->column->getLength($schema, $engineVersion);

        if ($length === null) {
            return null;
        }

        if ($generalSchema === false) {
            if ($length === 'max') {
                return '\'max\'';
            }
            return (string)$length;
        }

        if (str_replace(' ', '', (string)$length) !== $this->getDefaultLength()) {
            if ($length === 'max') {
                return '\'max\'';
            }
            return (string)$length;
        }

        return null;
    }

    private function getDefaultLength(): ?string
    {
        $defaultMapping = $this->column->getDefaultMapping();
        if ($defaultMapping !== null) {
            if (preg_match('/\(([\d,]+)\)/', $defaultMapping, $matches)) {
                return $matches[1];
            }
            if (strpos('(max)', $defaultMapping) !== false) {
                // MSSQL
                return 'max';
            }
        }

        return null;
    }

    /**
     * Builds general methods chain for column definition.
     * @param string $schema
     */
    private function buildGeneralDefinition(string $schema): void
    {
        array_unshift($this->definition, '$this');

        if ($this->isUnsignedPossible && $this->column->isUnsigned()) {
            $this->definition[] = 'unsigned()';
        }

        if ($this->isNotNullPossible && $this->column->isNotNull()) {
            $this->definition[] = 'notNull()';
        }

        $default = $this->column->getDefault();
        if ($default !== null) {
            if ($default instanceof Expression) {
                $this->definition[] = "defaultExpression('{$this->escapeQuotes($default->expression)}')";
            } elseif (is_array($default)) {
                $this->definition[] = "defaultValue('{$this->escapeQuotes(Json::encode($default))}')";
            } else {
                $this->definition[] = "defaultValue('{$this->escapeQuotes((string)$default)}')";
            }
        }

        $columnAppend = $this->column->getAppend();
        if (
            $this->isPrimaryKeyPossible
            && $this->primaryKey
            && $this->primaryKey->isComposite() === false
            && $this->column->isColumnInPrimaryKey($this->primaryKey)
        ) {
            $schemaAppend = $this->column->prepareSchemaAppend($schema, true, $this->column->isAutoIncrement());

            if (!empty($columnAppend)) {
                $schemaAppend .= ' ' . trim(str_replace($schemaAppend, '', $columnAppend));
            }
            $this->definition[] = "append('" . $this->escapeQuotes(trim($schemaAppend)) . "')";
        } elseif (!empty($columnAppend)) {
            $this->definition[] = "append('" . $this->escapeQuotes(trim($columnAppend)) . "')";
        }

        $comment = $this->column->getComment();
        if ($comment) {
            $this->definition[] = "comment('" . $this->escapeQuotes((string)$comment) . "')";
        }

        $after = $this->column->getAfter();
        if ($after) {
            $this->definition[] = "after('" . $this->escapeQuotes($after) . "')";
        } elseif ($this->column->isFirst()) {
            $this->definition[] = 'first()';
        }
    }

    /**
     * Escapes single quotes.
     * @param string $value
     * @return string
     */
    public function escapeQuotes(string $value): string
    {
        return str_replace('\'', '\\\'', $value);
    }

    /**
     * @param ColumnInterface $column
     */
    public function setColumn(ColumnInterface $column): void
    {
        $this->column = $column;
    }

    public function setPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }
}
