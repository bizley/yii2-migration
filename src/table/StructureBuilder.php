<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;

use function array_key_exists;
use function count;

final class StructureBuilder implements StructureBuilderInterface
{
    /**
     * Builds table structure based on the list of changes from the Updater.
     * @param array<StructureChangeInterface> $changes
     * @param string|null $schema
     * @return StructureInterface
     */
    public function build(array $changes, ?string $schema): StructureInterface
    {
        $structure = new Structure();

        /** @var StructureChangeInterface $change */
        foreach ($changes as $change) {
            if ($change instanceof StructureChangeInterface === false) {
                throw new InvalidArgumentException('You must provide array of StructureChangeInterface objects.');
            }

            switch ($change->getMethod()) {
                case 'createTable':
                    $this->applyCreateTableValue($structure, $change->getValue(), $schema);
                    break;

                case 'addColumn':
                case 'alterColumn':
                    $this->applyAddColumnValue($structure, $change->getValue(), $schema);
                    break;

                case 'dropColumn':
                    $this->applyDropColumnValue($structure, $change->getValue());
                    break;

                case 'renameColumn':
                    $this->applyRenameColumnValue($structure, $change->getValue());
                    break;

                case 'addPrimaryKey':
                    $this->applyAddPrimaryKeyValue($structure, $change->getValue(), $schema);
                    break;

                case 'dropPrimaryKey':
                    $this->applyDropPrimaryKeyValue($structure, $schema);
                    break;

                case 'addForeignKey':
                    $this->applyAddForeignKeyValue($structure, $change->getValue());
                    break;

                case 'dropForeignKey':
                    $this->applyDropForeignKeyValue($structure, $change->getValue());
                    break;

                case 'createIndex':
                    $this->applyCreateIndexValue($structure, $change->getValue());
                    break;

                case 'dropIndex':
                    $this->applyDropIndexValue($structure, $change->getValue());
                    break;

                case 'addCommentOnColumn':
                    $this->applyAddCommentOnColumnValue($structure, $change->getValue());
                    break;

                case 'dropCommentFromColumn':
                    $this->applyDropCommentFromColumnValue($structure, $change->getValue());
                    break;
            }
        }

        return $structure;
    }

    private function applyCreateTableValue(StructureInterface $structure, array $columns, ?string $schema): void
    {
        foreach ($columns as $column) {
            $this->applyAddColumnValue($structure, $column, $schema);
        }
    }

    private function applyAddColumnValue(StructureInterface $structure, ColumnInterface $column, ?string $schema): void
    {
        $structure->addColumn($column);

        if ($column->isPrimaryKey() || $column->isPrimaryKeyInfoAppended($schema)) {
            $primaryKey = $structure->getPrimaryKey();
            if ($primaryKey === null) {
                $primaryKey = new PrimaryKey();
                $primaryKey->setColumns([$column->getName()]);
            } else {
                $primaryKey->addColumn($column->getName());
            }
            $structure->setPrimaryKey($primaryKey);
        }
    }

    private function applyDropColumnValue(StructureInterface $structure, string $columnName): void
    {
        $structure->removeColumn($columnName);
    }

    private function applyRenameColumnValue(StructureInterface $structure, array $data): void
    {
        $oldColumn = $structure->getColumn($data['old']);
        if ($oldColumn) {
            $newColumn = clone $oldColumn;
            $newColumn->setName($data['new']);
            $structure->addColumn($newColumn);
            $structure->removeColumn($data['old']);
        }
    }

    private function applyAddPrimaryKeyValue(
        StructureInterface $structure,
        PrimaryKeyInterface $primaryKey,
        ?string $schema
    ): void {
        $structure->setPrimaryKey($primaryKey);

        $columns = $structure->getColumns();

        foreach ($primaryKey->getColumns() as $columnName) {
            if (array_key_exists($columnName, $columns)) {
                /** @var ColumnInterface $column */
                $column = $columns[$columnName];
                $columnAppend = $column->getAppend();
                if (empty($columnAppend)) {
                    $column->setAppend($column->prepareSchemaAppend(true, false, $schema));
                } elseif ($column->isPrimaryKeyInfoAppended($schema) === false) {
                    $column->setAppend($columnAppend . ' ' . $column->prepareSchemaAppend(true, false, $schema));
                }
            }
        }
    }

    private function applyDropPrimaryKeyValue(StructureInterface $structure, ?string $schema): void
    {
        $primaryKey = $structure->getPrimaryKey();
        if ($primaryKey) {
            $columns = $structure->getColumns();

            foreach ($primaryKey->getColumns() as $columnName) {
                /** @var ColumnInterface $column */
                $column = $columns[$columnName];
                $columnAppend = $column->getAppend();
                if (array_key_exists($column, $columns) && !empty($columnAppend)) {
                    $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
                }
            }
        }

        $structure->setPrimaryKey(null);
    }

    private function applyAddForeignKeyValue(StructureInterface $structure, ForeignKeyInterface $foreignKey): void
    {
        $structure->addForeignKey($foreignKey);
    }

    private function applyDropForeignKeyValue(StructureInterface $structure, string $name): void
    {
        $structure->removeForeignKey($name);
    }

    private function applyCreateIndexValue(StructureInterface $structure, IndexInterface $index): void
    {
        $structure->addIndex($index);

        $indexColumns = $index->getColumns();
        if (
            $index->isUnique()
            && count($indexColumns) === 1
            && array_key_exists($indexColumns[0], $structure->getColumns())
        ) {
            $structure->getColumn($indexColumns[0])->setUnique(true);
        }
    }

    private function applyDropIndexValue(StructureInterface $structure, string $name): void
    {
        $index = $structure->getIndex($name);
        if ($index) {
            $indexColumns = $index->getColumns();
            if (
                $index->isUnique()
                && count($indexColumns) === 1
                && array_key_exists($indexColumns[0], $structure->getColumns())
                && $structure->getColumn($indexColumns[0])->isUnique()
            ) {
                $structure->getColumn($indexColumns[0])->setUnique(false);
            }

            $structure->removeIndex($name);
        }
    }

    private function applyAddCommentOnColumnValue(StructureInterface $structure, array $data): void
    {
        $column = $structure->getColumn($data['name']);
        if ($column) {
            $column->setComment($data['comment']);
        }
    }

    private function applyDropCommentFromColumnValue(StructureInterface $structure, string $columnName): void
    {
        $column = $structure->getColumn($columnName);
        if ($column) {
            $column->setComment(null);
        }
    }
}
