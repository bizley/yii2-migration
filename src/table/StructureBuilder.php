<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;
use yii\base\InvalidArgumentException;

final class StructureBuilder implements StructureBuilderInterface
{
    /**
     * Builds table structure based on the list of changes from the Inspector.
     * @param array<StructureChangeInterface|mixed> $changes
     */
    public function build(array $changes, ?string $schema, ?string $engineVersion): StructureInterface
    {
        $structure = new Structure();

        foreach ($changes as $change) {
            if (!$change instanceof StructureChangeInterface) {
                throw new InvalidArgumentException('You must provide array of StructureChangeInterface objects.');
            }

            switch ($change->getMethod()) {
                case 'createTable':
                    $structure->setName($change->getTable());
                    $this->applyCreateTableValue(
                        $structure,
                        $change->getValue($schema, $engineVersion),
                        $schema,
                        $engineVersion
                    );
                    break;

                case 'addColumn':
                case 'alterColumn':
                    $this->applyAddColumnValue(
                        $structure,
                        $change->getValue($schema, $engineVersion),
                        $schema,
                        $engineVersion
                    );
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

        $this->addHiddenIndexes($structure, $schema);

        return $structure;
    }

    /**
     * Applies create table value.
     * @param array<ColumnInterface> $columns
     */
    private function applyCreateTableValue(
        StructureInterface $structure,
        array $columns,
        ?string $schema,
        ?string $engineVersion
    ): void {
        foreach ($columns as $column) {
            $this->applyAddColumnValue($structure, $column, $schema, $engineVersion);
        }
    }

    /**
     * Applies add column value.
     */
    private function applyAddColumnValue(
        StructureInterface $structure,
        ColumnInterface $column,
        ?string $schema,
        ?string $engineVersion
    ): void {
        if ($column->getLength($schema, $engineVersion) === null) {
            $column->setLength(
                Schema::getDefaultLength($schema, $column->getType(), $engineVersion),
                $schema,
                $engineVersion
            );
        }

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

    /**
     * Applies drop column value.
     */
    private function applyDropColumnValue(StructureInterface $structure, string $columnName): void
    {
        $structure->removeColumn($columnName);
    }

    /**
     * Applies rename column value.
     * @param array<string, string> $data
     */
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

    /**
     * Applies add primary key value.
     */
    private function applyAddPrimaryKeyValue(
        StructureInterface $structure,
        PrimaryKeyInterface $primaryKey,
        ?string $schema
    ): void {
        $structure->setPrimaryKey($primaryKey);

        $columns = $structure->getColumns();

        foreach ($primaryKey->getColumns() as $columnName) {
            if (\array_key_exists($columnName, $columns)) {
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

    /**
     * Applies drop primary key value.
     */
    private function applyDropPrimaryKeyValue(StructureInterface $structure, ?string $schema): void
    {
        $primaryKey = $structure->getPrimaryKey();
        if ($primaryKey) {
            $columns = $structure->getColumns();

            foreach ($primaryKey->getColumns() as $columnName) {
                $column = $columns[$columnName];
                $columnAppend = $column->getAppend();
                if (\array_key_exists($columnName, $columns) && !empty($columnAppend)) {
                    $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
                }
            }
        }

        $structure->setPrimaryKey(null);
    }

    /**
     * Applies add foreign key value.
     */
    private function applyAddForeignKeyValue(StructureInterface $structure, ForeignKeyInterface $foreignKey): void
    {
        $structure->addForeignKey($foreignKey);
    }

    /**
     * Applies drop foreign key value.
     */
    private function applyDropForeignKeyValue(StructureInterface $structure, string $name): void
    {
        $structure->removeForeignKey($name);
    }

    /**
     * Applies create index value.
     */
    private function applyCreateIndexValue(StructureInterface $structure, IndexInterface $index): void
    {
        $structure->addIndex($index);

        $indexColumns = $index->getColumns();
        if (
            $index->isUnique()
            && \count($indexColumns) === 1
            && \array_key_exists($indexColumns[0], $structure->getColumns())
        ) {
            /** @var ColumnInterface $column */
            $column = $structure->getColumn($indexColumns[0]);
            $column->setUnique(true);
        }
    }

    /**
     * Applies drop index value.
     */
    private function applyDropIndexValue(StructureInterface $structure, string $name): void
    {
        $index = $structure->getIndex($name);
        if ($index) {
            $indexColumns = $index->getColumns();
            if (
                $index->isUnique()
                && \count($indexColumns) === 1
                && \array_key_exists($indexColumns[0], $structure->getColumns())
                && ($column = $structure->getColumn($indexColumns[0])) !== null
                && $column->isUnique()
            ) {
                $column->setUnique(false);
            }

            $structure->removeIndex($name);
        }
    }

    /**
     * Applies add comment on column value.
     * @param array<string, string> $data
     */
    private function applyAddCommentOnColumnValue(StructureInterface $structure, array $data): void
    {
        $column = $structure->getColumn($data['column']);
        if ($column) {
            $column->setComment($data['comment']);
        }
    }

    /**
     * Applies drop comment from column value.
     */
    private function applyDropCommentFromColumnValue(StructureInterface $structure, string $columnName): void
    {
        $column = $structure->getColumn($columnName);
        if ($column) {
            $column->setComment(null);
        }
    }

    /**
     * Adds automatic indexes made by DB engine.
     */
    private function addHiddenIndexes(StructureInterface $structure, ?string $schema): void
    {
        if ($schema === Schema::MYSQL) {
            // MySQL automatically adds index for foreign key when it's not explicitly added

            $foreignKeys = $structure->getForeignKeys();
            $indexes = $structure->getIndexes();
            $primaryKey = $structure->getPrimaryKey();
            if ($primaryKey !== null) {
                // use primary key as a potential index
                $indexes[] = $primaryKey;
            }
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeyColumns = $foreignKey->getColumns();
                $foreignKeyColumnsCount = count($foreignKeyColumns);
                foreach ($indexes as $index) {
                    $indexColumns = $index->getColumns();
                    if ($foreignKeyColumnsCount === \count(\array_intersect_assoc($foreignKeyColumns, $indexColumns))) {
                        // any index matching the FK columns as the first columns will do
                        continue 2;
                    }
                }

                $index = new Index();
                $index->setName($foreignKey->getName());
                $index->setColumns($foreignKeyColumns);

                $structure->addIndex($index);
            }
        }
    }
}
