<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;
use yii\db\Expression;
use yii\helpers\Json;

use function array_unshift;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_repeat;
use function str_replace;
use function stripos;
use function trim;

abstract class Column extends BaseObject
{
    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var string */
    public $defaultMapping;

    /** @var bool|null */
    public $isNotNull;

    /** @var int */
    public $size;

    /** @var int */
    public $precision;

    /** @var int */
    public $scale;

    /** @var bool */
    public $isUnique = false;

    /** @var bool */
    public $isUnsigned = false;

    /** @var string */
    public $check;

    /** @var mixed */
    public $default;

    /** @var bool */
    public $isPrimaryKey = false;

    /** @var bool */
    public $autoIncrement = false;

    /** @var string */
    public $append;

    /** @var string */
    public $comment;

    /** @var string */
    public $schema;

    /** @var string */
    public $after;

    /** @var bool */
    public $isFirst = false;

    /** @var string */
    public $engineVersion;

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    abstract public function setLength($value): void;

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    abstract public function getLength();

    abstract protected function buildSpecificDefinition(Structure $table): void;

    /** @var array */
    protected $definition = [];
    /** @var bool */
    protected $isUnsignedPossible = true;
    /** @var bool */
    protected $isNotNullPossible = true;
    /** @var bool */
    protected $isPkPossible = true;

    /**
     * Builds general methods chain for column definition.
     * @param Structure $table
     */
    protected function buildGeneralDefinition(Structure $table): void
    {
        array_unshift($this->definition, '$this');

        if ($this->isUnsignedPossible && $this->isUnsigned) {
            $this->definition[] = 'unsigned()';
        }

        if ($this->isNotNullPossible && $this->isNotNull) {
            $this->definition[] = 'notNull()';
        }

        if ($this->default !== null) {
            if ($this->default instanceof Expression) {
                $this->definition[] = "defaultExpression('" . $this->escapeQuotes($this->default->expression) . "')";
            } elseif (is_array($this->default)) {
                $this->definition[] = "defaultValue('" . $this->escapeQuotes(Json::encode($this->default)) . "')";
            } else {
                $this->definition[] = "defaultValue('" . $this->escapeQuotes((string)$this->default) . "')";
            }
        }

        if (
            $this->isPkPossible
            && $table->primaryKey
            && $table->primaryKey->isComposite() === false
            && $this->isColumnInPrimaryKey($table->primaryKey)
        ) {
            $append = $this->prepareSchemaAppend(true, $this->autoIncrement);
            if (!empty($this->append)) {
                $append .= ' ' . trim(str_replace($append, '', $this->append));
            }

            $this->definition[] = "append('" . $this->escapeQuotes(trim($append)) . "')";
        } elseif (!empty($this->append)) {
            $this->definition[] = "append('" . $this->escapeQuotes(trim((string)$this->append)) . "')";
        }

        if ($this->comment) {
            $this->definition[] = "comment('" . $this->escapeQuotes((string)$this->comment) . "')";
        }

        if ($this->after) {
            $this->definition[] = "after('" . $this->escapeQuotes($this->after) . "')";
        } elseif ($this->isFirst) {
            $this->definition[] = 'first()';
        }
    }

    /**
     * Renders column definition.
     * @param Structure $table
     * @return string
     */
    public function renderDefinition(Structure $table): string
    {
        $this->buildSpecificDefinition($table);
        $this->buildGeneralDefinition($table);

        return implode('->', $this->definition);
    }

    /**
     * Renders the column.
     * @param Structure $table
     * @param int $indent
     * @return string
     */
    public function render(Structure $table, int $indent = 12): string
    {
        return str_repeat(' ', $indent) . "'{$this->name}' => " . $this->renderDefinition($table) . ',';
    }

    /**
     * Checks if column is a part of primary key.
     * @param PrimaryKey $pk
     * @return bool
     */
    public function isColumnInPrimaryKey(PrimaryKey $pk): bool
    {
        return in_array($this->name, $pk->columns, true);
    }

    /**
     * Checks if information of primary key is set in append property.
     * @return bool
     */
    public function isPrimaryKeyInfoAppended(): bool
    {
        if (empty($this->append)) {
            return false;
        }

        if (stripos($this->append, 'PRIMARY KEY') !== false) {
            return !($this->schema === Structure::SCHEMA_MSSQL && stripos($this->append, 'IDENTITY') === false);
        }

        return false;
    }

    /**
     * Prepares append SQL based on schema.
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string|null
     */
    public function prepareSchemaAppend(bool $primaryKey, bool $autoIncrement): ?string
    {
        switch ($this->schema) {
            case Structure::SCHEMA_MSSQL:
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;

            case Structure::SCHEMA_OCI:
            case Structure::SCHEMA_PGSQL:
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;

            case Structure::SCHEMA_SQLITE:
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;

            case Structure::SCHEMA_CUBRID:
            case Structure::SCHEMA_MYSQL:
            default:
                $append = trim(($autoIncrement ? 'AUTO_INCREMENT ' : '') . ($primaryKey ? 'PRIMARY KEY' : ''));
        }

        return empty($append) ? null : $append;
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
     * Removes information of primary key in append property and returns what is left.
     * @return null|string
     */
    public function removeAppendedPrimaryKeyInfo(): ?string
    {
        if ($this->isPrimaryKeyInfoAppended() === false) {
            return $this->append;
        }

        switch ($this->schema) {
            case Structure::SCHEMA_MSSQL:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'IDENTITY'], '', $this->append);
                break;

            case Structure::SCHEMA_OCI:
            case Structure::SCHEMA_PGSQL:
                $cleanedAppend = str_ireplace('PRIMARY KEY', '', $this->append);
                break;

            case Structure::SCHEMA_SQLITE:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $this->append);
                break;

            case Structure::SCHEMA_CUBRID:
            case Structure::SCHEMA_MYSQL:
            default:
                $cleanedAppend = str_ireplace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $this->append);
        }

        $cleanedAppend = trim(preg_replace('/\s+/', ' ', $cleanedAppend));

        return !empty($cleanedAppend) ? $cleanedAppend : null;
    }

    /**
     * @param bool $generalSchema
     * @return string|null
     */
    public function getRenderLength(bool $generalSchema): ?string
    {
        $length = $this->getLength();

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
        if ($this->defaultMapping !== null) {
            if (preg_match('/\(([\d,]+)\)/', $this->defaultMapping, $matches)) {
                return $matches[1];
            }
            if (preg_match('/\(max\)/', $this->defaultMapping)) {
                // MSSQL
                return 'max';
            }
        }

        return null;
    }
}
