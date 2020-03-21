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
use function str_repeat;
use function str_replace;
use function strpos;
use function trim;

final class ColumnRenderer implements ColumnRendererInterface
{
    /** @var array */
    private $definition = [];

    /** @var bool */
    private $isUnsignedPossible = true;

    /** @var bool */
    private $isNotNullPossible = true;

    /** @var bool */
    private $isPrimaryKeyPossible = true;

    /** @var string */
    private $template = "'{columnName}' => {columnDefinition},";

    /** @var bool */
    private $generalSchema;

    public function __construct(bool $generalSchema)
    {
        $this->generalSchema = $generalSchema;
    }

    public function render(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null
    ): ?string {
        $template = str_repeat(' ', $indent) . $this->template;

        return str_replace(
            [
                '{columnName}',
                '{columnDefinition}'
            ],
            [
                $column->getName(),
                $this->renderDefinition($column, $primaryKey, $schema, $engineVersion)
            ],
            $template
        );
    }

    /**
     * Renders column definition.
     * @param ColumnInterface $column
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
     */
    public function renderDefinition(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        string $schema = null,
        string $engineVersion = null
    ): ?string {
        $this->definition = [];

        $this->buildColumnDefinition($column, $primaryKey, $schema, $engineVersion);
        $this->buildGeneralDefinition($column, $primaryKey, $schema);

        return implode('->', $this->definition);
    }

    private function buildColumnDefinition(
        ColumnInterface $column,
        ?PrimaryKeyInterface $primaryKey,
        string $schema = null,
        string $engineVersion = null
    ): void {
        $definition = $column->getDefinition();

        if ($this->generalSchema) {
            if ($column instanceof PrimaryKeyColumnInterface) {
                $this->isPrimaryKeyPossible = false;
                $this->isNotNullPossible = false;
            } elseif (
                $column instanceof PrimaryKeyVariantColumnInterface
                && $primaryKey
                && $primaryKey->isComposite() === false
                && $column->isColumnInPrimaryKey($primaryKey)
            ) {
                $this->isPrimaryKeyPossible = false;
                $this->isNotNullPossible = false;
                $definition = $column->getPrimaryKeyDefinition();
            }
        }

        $this->definition[] = str_replace(
            '{renderLength}',
            $this->getRenderedLength($column, $schema, $engineVersion),
            $definition
        );
    }

    /**
     * @param ColumnInterface $column
     * @param string $schema
     * @param string|null $engineVersion
     * @return string|null
     */
    private function getRenderedLength(
        ColumnInterface $column,
        string $schema = null,
        string $engineVersion = null
    ): ?string {
        $length = $column->getLength($schema, $engineVersion);

        if ($length === null) {
            return null;
        }

        if (
            $this->generalSchema === false
            || str_replace(' ', '', (string)$length) !== $this->getDefaultLength($column)
        ) {
            if ($length === 'max') {
                return '\'max\'';
            }
            return (string)$length;
        }

        // default value should be null to be automatically set to schema's default for column
        return null;
    }

    private function getDefaultLength(ColumnInterface $column): ?string
    {
        $defaultMapping = $column->getDefaultMapping();
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
     * @param ColumnInterface $column
     * @param PrimaryKeyInterface|null $primaryKey
     * @param string|null $schema
     */
    private function buildGeneralDefinition(
        ColumnInterface $column,
        ?PrimaryKeyInterface $primaryKey,
        string $schema = null
    ): void {
        array_unshift($this->definition, '$this');

        if ($this->isUnsignedPossible && $column->isUnsigned()) {
            $this->definition[] = 'unsigned()';
        }

        if ($this->isNotNullPossible && $column->isNotNull()) {
            $this->definition[] = 'notNull()';
        }

        $default = $column->getDefault();
        if ($default !== null) {
            if ($default instanceof Expression) {
                $this->definition[] = "defaultExpression('{$this->escapeQuotes($default->expression)}')";
            } elseif (is_array($default)) {
                $this->definition[] = "defaultValue('{$this->escapeQuotes(Json::encode($default))}')";
            } else {
                $this->definition[] = "defaultValue('{$this->escapeQuotes((string)$default)}')";
            }
        }

        $columnAppend = $column->getAppend();
        if (
            $this->isPrimaryKeyPossible
            && $primaryKey
            && $primaryKey->isComposite() === false
            && $column->isColumnInPrimaryKey($primaryKey)
        ) {
            $schemaAppend = $column->prepareSchemaAppend(true, $column->isAutoIncrement(), $schema);

            if (!empty($columnAppend)) {
                $schemaAppend .= ' ' . trim(str_replace($schemaAppend, '', $columnAppend));
            }
            $this->definition[] = "append('" . $this->escapeQuotes(trim($schemaAppend)) . "')";
        } elseif (!empty($columnAppend)) {
            $this->definition[] = "append('" . $this->escapeQuotes(trim($columnAppend)) . "')";
        }

        $comment = $column->getComment();
        if ($comment) {
            $this->definition[] = "comment('" . $this->escapeQuotes((string)$comment) . "')";
        }

        $after = $column->getAfter();
        if ($after) {
            $this->definition[] = "after('" . $this->escapeQuotes($after) . "')";
        } elseif ($column->isFirst()) {
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
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
