<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function array_key_exists;

class Structure implements StructureInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var PrimaryKey
     */
    private $primaryKey;

    /**
     * @var array<Column>
     */
    private $columns = [];

    /**
     * @var array<Index>
     */
    private $indexes = [];

    /**
     * @var array<ForeignKey>
     */
    private $foreignKeys = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return PrimaryKey
     */
    public function getPrimaryKey(): PrimaryKey
    {
        return $this->primaryKey;
    }

    /**
     * @param PrimaryKey $primaryKey
     */
    public function setPrimaryKey(PrimaryKey $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param array $indexes
     */
    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function setForeignKeys(array $foreignKeys): void
    {
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * Builds table structure based on the list of changes from the Updater.
     * @param array<Change> $changes
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function applyChanges(array $changes): void
    {
        /** @var $change Change */
        foreach ($changes as $change) {
            if ($change instanceof Change === false) {
                throw new InvalidArgumentException('You must provide array of Change objects.');
            }

            $value = $change->getValue();

            switch ($change->method) {
                case 'createTable':
                    /* @var $column Column */
                    foreach ($value as $column) {
                        $this->columns[$column->name] = $column;

                        if ($column->isPrimaryKey || $column->isPrimaryKeyInfoAppended()) {
                            if ($this->primaryKey === null) {
                                $this->primaryKey = new PrimaryKey(['columns' => [$column->name]]);
                            } else {
                                $this->primaryKey->addColumn($column->name);
                            }
                        }
                    }
                    break;

                case 'addColumn':
                    $this->columns[$value->name] = $value;

                    if ($value->isPrimaryKey || $value->isPrimaryKeyInfoAppended()) {
                        if ($this->primaryKey === null) {
                            $this->primaryKey = new PrimaryKey(['columns' => [$value->name]]);
                        } else {
                            $this->primaryKey->addColumn($value->name);
                        }
                    }
                    break;

                case 'dropColumn':
                    unset($this->columns[$value]);
                    break;

                case 'renameColumn':
                    if (array_key_exists($value['old'], $this->columns)) {
                        $this->columns[$value['new']] = $this->columns[$value['old']];
                        $this->columns[$value['new']]->name = $value['new'];

                        unset($this->columns[$value['old']]);
                    }
                    break;

                case 'alterColumn':
                    $this->columns[$value->name] = $value;
                    break;

                case 'addPrimaryKey':
                    $this->primaryKey = $value;

                    foreach ($this->primaryKey->columns as $column) {
                        if (array_key_exists($column, $this->columns)) {
                            if (empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append = $this->columns[$column]->prepareSchemaAppend(
                                    true,
                                    false
                                );
                            } elseif (!$this->columns[$column]->isPrimaryKeyInfoAppended()) {
                                $this->columns[$column]->append .= ' ' . $this->columns[$column]->prepareSchemaAppend(
                                    true,
                                    false
                                );
                            }
                        }
                    }
                    break;

                case 'dropPrimaryKey':
                    if ($this->primaryKey !== null) {
                        foreach ($this->primaryKey->columns as $column) {
                            if (array_key_exists($column, $this->columns) && !empty($this->columns[$column]->append)) {
                                $this->columns[$column]->append
                                    = $this->columns[$column]->removeAppendedPrimaryKeyInfo();
                            }
                        }
                    }

                    $this->primaryKey = null;
                    break;

                case 'addForeignKey':
                    $this->foreignKeys[$value->name] = $value;
                    break;

                case 'dropForeignKey':
                    unset($this->foreignKeys[$value]);
                    break;

                case 'createIndex':
                    $this->indexes[$value->name] = $value;
                    if (
                        $value->unique
                        && array_key_exists($value->columns[0], $this->columns)
                        && count($value->columns) === 1
                    ) {
                        $this->columns[$value->columns[0]]->isUnique = true;
                    }
                    break;

                case 'dropIndex':
                    if (
                        $this->indexes[$value]->unique
                        && count($this->indexes[$value]->columns) === 1
                        && array_key_exists($this->indexes[$value]->columns[0], $this->columns)
                        && $this->columns[$this->indexes[$value]->columns[0]]->isUnique
                    ) {
                        $this->columns[$this->indexes[$value]->columns[0]]->isUnique = false;
                    }
                    unset($this->indexes[$value]);
                    break;

                case 'addCommentOnColumn':
                    if (array_key_exists($value->name, $this->columns)) {
                        $this->columns[$value->name]->comment = $value->comment;
                    }
                    break;

                case 'dropCommentFromColumn':
                    if (array_key_exists($value, $this->columns)) {
                        $this->columns[$value]->comment = null;
                    }
            }
        }
    }
}
