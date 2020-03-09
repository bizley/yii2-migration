<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function array_key_exists;
use function count;

class StructureBuilder
{
    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var string
     */
    private $schema;

    public function __construct(StructureInterface $structure, string $schema)
    {
        $this->structure = $structure;
        $this->schema = $schema;
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

            switch ($change->getMethod()) {
                case 'createTable':
                    $this->applyCreateTableValue($change->getValue());
                    break;

                case 'addColumn':
                case 'alterColumn':
                    $this->applyAddColumnValue($change->getValue());
                    break;

                case 'dropColumn':
                    $this->applyDropColumnValue($change->getValue());
                    break;

                case 'renameColumn':
                    $this->applyRenameColumnValue($change->getValue());
                    break;

                case 'addPrimaryKey':
                    $this->applyAddPrimaryKeyValue($change->getValue());
                    break;

                case 'dropPrimaryKey':
                    $this->applyDropPrimaryKeyValue();
                    break;

                case 'addForeignKey':
                    $this->applyAddForeignKeyValue($change->getValue());
                    break;

                case 'dropForeignKey':
                    $this->applyDropForeignKeyValue($change->getValue());
                    break;

                case 'createIndex':
                    $this->applyCreateIndexValue($change->getValue());
                    break;

                case 'dropIndex':
                    $this->applyDropIndexValue($change->getValue());
                    break;

                case 'addCommentOnColumn':
                    $this->applyAddCommentOnColumnValue($change->getValue());
                    break;

                case 'dropCommentFromColumn':
                    $this->applyDropCommentFromColumnValue($change->getValue());
                    break;
            }
        }
    }

    private function applyCreateTableValue(array $columns): void
    {
        foreach ($columns as $column) {
            $this->applyAddColumnValue($column);
        }
    }

    private function applyAddColumnValue(ColumnInterface $column): void
    {
        $this->structure->addColumn($column);

        if ($column->isPrimaryKey() || $column->isPrimaryKeyInfoAppended($this->schema)) {
            $primaryKey = $this->structure->getPrimaryKey();
            if ($primaryKey === null) {
                $primaryKey = new PrimaryKey();
                $primaryKey->setColumns([$column->getName()]);
            } else {
                $primaryKey->addColumn($column->getName());
            }
            $this->structure->setPrimaryKey($primaryKey);
        }
    }

    private function applyDropColumnValue(string $columnName): void
    {
        $this->structure->removeColumn($columnName);
    }

    private function applyRenameColumnValue(array $data): void
    {
        $oldColumn = $this->structure->getColumn($data['old']);
        if ($oldColumn) {
            $newColumn = clone $oldColumn;
            $newColumn->setName($data['new']);
            $this->structure->addColumn($newColumn);
            $this->structure->removeColumn($data['old']);
        }
    }

    private function applyAddPrimaryKeyValue(PrimaryKeyInterface $primaryKey): void
    {
        $this->structure->setPrimaryKey($primaryKey);

        $columns = $this->structure->getColumns();

        foreach ($primaryKey->getColumns() as $columnName) {
            if (array_key_exists($columnName, $columns)) {
                /** @var ColumnInterface $column */
                $column = $columns[$columnName];
                $columnAppend = $column->getAppend();
                if (empty($columnAppend)) {
                    $column->setAppend($column->prepareSchemaAppend($this->schema, true, false));
                } elseif ($column->isPrimaryKeyInfoAppended($this->schema) === false) {
                    $column->setAppend($columnAppend . ' ' . $column->prepareSchemaAppend($this->schema, true, false));
                }
            }
        }
    }

    private function applyDropPrimaryKeyValue(): void
    {
        $primaryKey = $this->structure->getPrimaryKey();
        if ($primaryKey) {
            $columns = $this->structure->getColumns();

            foreach ($primaryKey->getColumns() as $columnName) {
                /** @var ColumnInterface $column */
                $column = $columns[$columnName];
                $columnAppend = $column->getAppend();
                if (array_key_exists($column, $columns) && !empty($columnAppend)) {
                    $column->setAppend($column->removeAppendedPrimaryKeyInfo($this->schema));
                }
            }
        }

        $this->structure->setPrimaryKey(null);
    }

    private function applyAddForeignKeyValue(ForeignKeyInterface $foreignKey): void
    {
        $this->structure->addForeignKey($foreignKey);
    }

    private function applyDropForeignKeyValue(string $name): void
    {
        $this->structure->removeForeignKey($name);
    }

    private function applyCreateIndexValue(IndexInterface $index): void
    {
        $this->structure->addIndex($index);

        $indexColumns = $index->getColumns();
        if (
            $index->isUnique()
            && count($indexColumns) === 1
            && array_key_exists($indexColumns[0], $this->structure->getColumns())
        ) {
            $this->structure->getColumn($indexColumns[0])->setUnique(true);
        }
    }

    private function applyDropIndexValue(string $name): void
    {
        $index = $this->structure->getIndex($name);
        if ($index) {
            $indexColumns = $index->getColumns();
            if (
                $index->isUnique()
                && count($indexColumns) === 1
                && array_key_exists($indexColumns[0], $this->structure->getColumns())
                && $this->structure->getColumn($indexColumns[0])->isUnique()
            ) {
                $this->structure->getColumn($indexColumns[0])->setUnique(false);
            }

            $this->structure->removeIndex($name);
        }
    }

    private function applyAddCommentOnColumnValue(array $data): void
    {
        $column = $this->structure->getColumn($data['name']);
        if ($column) {
            $column->setComment($data['comment']);
        }
    }

    private function applyDropCommentFromColumnValue(string $columnName): void
    {
        $column = $this->structure->getColumn($columnName);
        if ($column) {
            $column->setComment(null);
        }
    }
}
