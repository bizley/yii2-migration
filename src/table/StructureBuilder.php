<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function array_key_exists;

class StructureBuilder
{
    /**
     * @var StructureInterface
     */
    private $structure;

    public function __construct(StructureInterface $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Builds table structure based on the list of changes from the Updater.
     * @param array<StructureChange> $changes
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function apply(array $changes): void
    {
        /** @var $change StructureChange */
        foreach ($changes as $change) {
            if ($change instanceof StructureChange === false) {
                throw new InvalidArgumentException('You must provide array of Change objects.');
            }

            $value = $change->getValue();

            switch ($change->getMethod()) {
                case 'createTable':
                    $this->applyCreateTableValue($change->getValue());
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

    private function applyCreateTableValue(array $columns): void
    {
        /** @var Column $column */
        foreach ($columns as $column) {
            $this->structure->addColumn($column);

            if ($column->isPrimaryKey() || $column->isPrimaryKeyInfoAppended()) {
                $primaryKey = $this->structure->getPrimaryKey();
                if ($primaryKey === null) {
                    $primaryKey = new PrimaryKey();
                    $primaryKey->setColumns([$column->name]);
                } else {
                    $primaryKey->addColumn($column->getName());
                }
                $this->structure->setPrimaryKey($primaryKey);
            }
        }
    }
}
