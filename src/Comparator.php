<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\StructureInterface;
use yii\base\NotSupportedException;
use yii\helpers\Json;

use function count;
use function in_array;
use function is_string;

final class Comparator implements ComparatorInterface
{
    /** @var bool */
    private $generalSchema;

    public function __construct(bool $generalSchema)
    {
        $this->generalSchema = $generalSchema;
    }

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param BlueprintInterface $blueprint
     * @param bool $onlyShow whether changes should be only displayed
     * @param string|null $schema
     * @param string|null $engineVersion
     * @throws NotSupportedException
     */
    public function compare(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void {
        $this->compareColumns($newStructure, $oldStructure, $blueprint, $onlyShow, $schema, $engineVersion);
        $this->comparePrimaryKeys(
            $newStructure->getPrimaryKey(),
            $oldStructure->getPrimaryKey(),
            $blueprint,
            $onlyShow,
            $schema
        );
        $this->compareForeignKeys($newStructure, $oldStructure, $blueprint, $onlyShow, $schema);
        $this->compareIndexes($newStructure, $oldStructure, $blueprint);
    }

    /**
     * Compares the columns between new and old structure.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param BlueprintInterface $blueprint
     * @param bool $onlyShow
     * @param string|null $schema
     * @param string|null $engineVersion
     * @throws NotSupportedException
     */
    private function compareColumns(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void {
        $previousColumn = null;
        $newColumns = $newStructure->getColumns();
        $newPrimaryKey = $newStructure->getPrimaryKey();
        $oldColumns = $oldStructure->getColumns();

        /** @var ColumnInterface $column */
        /** @var string $name */
        foreach ($newColumns as $name => $column) {
            if (array_key_exists($name, $oldColumns) === false) {
                $blueprint->addDescription("missing column '$name'");
                if ($previousColumn) {
                    $column->setAfter($previousColumn);
                } else {
                    $column->setFirst(true);
                }
                $blueprint->addColumn($column);

                $previousColumn = $name;
                continue;
            }

            if (
                $this->generalSchema === false
                && $newPrimaryKey
                && $column->getAppend() === null
                && $newPrimaryKey->isComposite() === false
                && $column->isColumnInPrimaryKey($newPrimaryKey)
            ) {
                $column->setAppend($column->prepareSchemaAppend(true, $column->isAutoIncrement(), $schema));
            }

            $previousColumn = $name;
            foreach (
                [
                    'getType' => 'type',
                    'isNotNull' => 'not null',
                    'getLength' => 'length',
                    'isUnique' => 'unique',
                    'isUnsigned' => 'unsigned',
                    'getDefault' => 'default',
                    'getAppend' => 'append',
                    'getComment' => 'comment',
                ] as $propertyFetch => $propertyName
            ) {
                /** @var ColumnInterface $oldColumn */
                $oldColumn = $oldStructure->getColumn($name);
                if ($propertyFetch === 'getLength') {
                    $oldProperty = $oldColumn->getLength($schema, $engineVersion);
                    $newProperty = $column->getLength($schema, $engineVersion);
                } else {
                    $oldProperty = $oldColumn->$propertyFetch();
                    $newProperty = $column->$propertyFetch();
                }
                if (is_bool($oldProperty) === false && $oldProperty !== null && is_array($oldProperty) === false) {
                    $oldProperty = (string)$oldProperty;
                }
                if (is_bool($newProperty) === false && $newProperty !== null && is_array($newProperty) === false) {
                    $newProperty = (string)$newProperty;
                }
                if ($oldProperty !== $newProperty) {
                    if ($propertyFetch === 'getAppend' && $this->isAppendSame($column, $oldColumn)) {
                        continue;
                    }

                    if (
                        $propertyFetch === 'isUnique'
                        && $this->isUniqueSameWithIndexes(
                            $newStructure,
                            $oldStructure,
                            $name,
                            (bool)$newProperty,
                            (bool)$oldProperty
                        )
                    ) {
                        continue;
                    }

                    $blueprint->addDescription(
                        "different '$name' column property: $propertyName ("
                        . 'DB: ' . $this->stringifyValue($newProperty) . ' != '
                        . 'MIG: ' . $this->stringifyValue($oldProperty) . ')'
                    );

                    if ($schema === Schema::SQLITE) {
                        $blueprint->addDescription(
                            '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
                        );
                        if ($onlyShow === false) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }
                    }
                    $blueprint->alterColumn($column);
                    $blueprint->reverseColumn($oldColumn);
                }
            }
        }

        /** @var ColumnInterface $column */
        foreach ($oldColumns as $name => $column) {
            if (array_key_exists($name, $newColumns) === false) {
                $blueprint->addDescription("excessive column '$name'");
                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP COLUMN is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }
                }

                $blueprint->dropColumn($column);
            }
        }
    }

    /**
     * Compares the foreign keys between new and old structure.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param BlueprintInterface $blueprint
     * @param bool $onlyShow
     * @param string|null $schema
     * @throws NotSupportedException
     */
    private function compareForeignKeys(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema
    ): void {
        $newForeignKeys = $newStructure->getForeignKeys();
        $oldForeignKeys = $oldStructure->getForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($newForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $oldForeignKeys) === false) {
                $blueprint->addDescription("missing foreign key '$name'");

                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $blueprint->addForeignKey($foreignKey);

                continue;
            }

            /** @var ForeignKeyInterface $oldForeignKey */
            $oldForeignKey = $oldStructure->getForeignKey($name);
            $newForeignKeyColumns = $foreignKey->getColumns();
            $oldForeignKeyColumns = $oldForeignKey->getColumns();
            $intersection = array_intersect($newForeignKeyColumns, $oldForeignKeyColumns);

            if (
                count(
                    array_merge(
                        array_diff($newForeignKeyColumns, $intersection),
                        array_diff($oldForeignKeyColumns, $intersection)
                    )
                )
            ) {
                $blueprint->addDescription(
                    "different foreign key '$name' columns ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyColumns) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyColumns) . ')'
                );

                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $blueprint->dropForeignKey($oldForeignKey);
                $blueprint->addForeignKey($foreignKey);

                continue;
            }

            $newForeignKeyReferredColumns = $foreignKey->getReferredColumns();
            $oldForeignKeyReferredColumns = $oldForeignKey->getReferredColumns();
            $intersection = array_intersect($newForeignKeyReferredColumns, $oldForeignKeyReferredColumns);

            if (
                count(
                    array_merge(
                        array_diff($newForeignKeyReferredColumns, $intersection),
                        array_diff($oldForeignKeyReferredColumns, $intersection)
                    )
                )
            ) {
                $blueprint->addDescription(
                    "different foreign key '$name' referred columns ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyReferredColumns) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyReferredColumns) . ')'
                );

                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $blueprint->dropForeignKey($oldForeignKey);
                $blueprint->addForeignKey($foreignKey);
            }

            $newForeignKeyReferredTable = $foreignKey->getReferredTable();
            $oldForeignKeyReferredTable = $oldForeignKey->getReferredTable();
            if ($newForeignKeyReferredTable !== $oldForeignKeyReferredTable) {
                $blueprint->addDescription(
                    "different foreign key '$name' referred table ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyReferredTable) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyReferredTable) . ')'
                );

                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $blueprint->dropForeignKey($oldForeignKey);
                $blueprint->addForeignKey($foreignKey);
            }
        }

        /** @var ForeignKeyInterface $foreignKey */
        foreach ($oldForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $newForeignKeys) === false) {
                $blueprint->addDescription("excessive foreign key '$name'");

                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $blueprint->dropForeignKey($foreignKey);
            }
        }
    }

    /**
     * Compares the primary keys between new and old structure.
     * @param PrimaryKeyInterface|null $newPrimaryKey
     * @param PrimaryKeyInterface|null $oldPrimaryKey
     * @param BlueprintInterface $blueprint
     * @param bool $onlyShow
     * @param string|null $schema
     * @throws NotSupportedException
     */
    private function comparePrimaryKeys(
        ?PrimaryKeyInterface $newPrimaryKey,
        ?PrimaryKeyInterface $oldPrimaryKey,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema
    ): void {
        $blueprint->setTableNewPrimaryKey($newPrimaryKey);
        $blueprint->setTableOldPrimaryKey($oldPrimaryKey);

        $newPrimaryKeyColumns = $newPrimaryKey ? $newPrimaryKey->getColumns() : [];
        $oldPrimaryKeyColumns = $oldPrimaryKey ? $oldPrimaryKey->getColumns() : [];
        $intersection = array_intersect($newPrimaryKeyColumns, $oldPrimaryKeyColumns);

        $differentColumns = array_merge(
            array_diff($newPrimaryKeyColumns, $intersection),
            array_diff($oldPrimaryKeyColumns, $intersection)
        );

        if (count($differentColumns)) {
            $blueprint->addDescription('different primary key definition');

            $alreadyDropped = false;
            if (count($oldPrimaryKeyColumns)) {
                if ($schema === Schema::SQLITE) {
                    $blueprint->addDescription(
                        '(!) DROP PRIMARY KEY is not supported by SQLite: Migration must be created manually'
                    );
                    $alreadyDropped = true;
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }
                }

                /** @var PrimaryKeyInterface $oldPrimaryKey */
                $blueprint->dropPrimaryKey($oldPrimaryKey);
            }

            $newPrimaryKeyColumnsCount = count($newPrimaryKeyColumns);
            if ($this->shouldPrimaryKeyBeAdded($blueprint, $differentColumns, $newPrimaryKeyColumnsCount, $schema)) {
                $this->removeExcessivePrimaryKeyStatements(
                    $blueprint,
                    $differentColumns,
                    $newPrimaryKeyColumnsCount,
                    $schema
                );

                if ($schema === Schema::SQLITE) {
                    if ($alreadyDropped === false) {
                        $blueprint->addDescription(
                            '(!) ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually'
                        );
                    }
                    if ($onlyShow === false) {
                        throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
                    }
                }

                /** @var PrimaryKeyInterface $newPrimaryKey */
                $blueprint->addPrimaryKey($newPrimaryKey);
            }
        }
    }

    /**
     * Removes excessive primary key statements from the column in case the primary key will be added separately anyway.
     * @param BlueprintInterface $blueprint
     * @param array<string> $differentColumns
     * @param int $newPrimaryKeyColumnsCount
     * @param string|null $schema
     */
    private function removeExcessivePrimaryKeyStatements(
        BlueprintInterface $blueprint,
        array $differentColumns,
        int $newPrimaryKeyColumnsCount,
        ?string $schema
    ): void {
        if ($newPrimaryKeyColumnsCount > 1) {
            $addedColumns = $blueprint->getAddedColumns();
            $alteredColumns = $blueprint->getAlteredColumns();
            foreach ($differentColumns as $differentColumn) {
                /** @var ColumnInterface $column */
                foreach ($addedColumns as $name => $column) {
                    if ($name === $differentColumn) {
                        $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
                        break;
                    }
                }

                foreach ($alteredColumns as $name => $column) {
                    if ($name === $differentColumn) {
                        $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
                        break;
                    }
                }
            }
        }
    }

    /**
     * Checks whether the separate primary key needs to be added.
     * @param BlueprintInterface $blueprint
     * @param array<string> $differentColumns
     * @param int $newColumnsCount
     * @param string|null $schema
     * @return bool
     */
    private function shouldPrimaryKeyBeAdded(
        BlueprintInterface $blueprint,
        array $differentColumns,
        int $newColumnsCount,
        ?string $schema
    ): bool {
        if ($newColumnsCount === 0) {
            return false;
        }
        if ($newColumnsCount === 1 && count($differentColumns) === 1) {
            $addedColumns = $blueprint->getAddedColumns();
            /** @var ColumnInterface $column */
            foreach ($addedColumns as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($schema)) {
                    return false;
                }
            }

            $alteredColumns = $blueprint->getAlteredColumns();
            foreach ($alteredColumns as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($schema)) {
                    return false;
                }
            }

            return true;
        }

        return true;
    }

    /**
     * Compares the indexes between new and old structure.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param BlueprintInterface $blueprint
     */
    private function compareIndexes(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint
    ): void {
        $newIndexes = $newStructure->getIndexes();
        $oldIndexes = $oldStructure->getIndexes();

        /** @var IndexInterface $index */
        foreach ($newIndexes as $name => $index) {
            if (array_key_exists($name, $oldIndexes) === false) {
                $indexColumns = $index->getColumns();
                if (
                    $index->isUnique()
                    && count($indexColumns) === 1
                    && ($newIndexColumn = $newStructure->getColumn($indexColumns[0]))
                    && $newIndexColumn->isUnique()
                    && ($oldIndexColumn = $oldStructure->getColumn($indexColumns[0]))
                    && $oldIndexColumn->isUnique()
                ) {
                    // index is created for one unique column and this uniqueness has not changed
                    continue;
                }

                $foreignKeys = $newStructure->getForeignKeys();
                /** @var ForeignKeyInterface $foreignKey */
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getColumns() === $indexColumns) {
                        // index is created for foreign key with the same columns
                        continue 2;
                    }
                }

                $blueprint->addDescription("missing index '$name'");
                $blueprint->addIndex($index);

                continue;
            }

            /** @var IndexInterface $oldIndex */
            $oldIndex = $oldStructure->getIndex($name);
            if ($oldIndex->isUnique() !== $index->isUnique()) {
                $blueprint->addDescription(
                    "different index '$name' definition (DB: unique "
                    . $this->stringifyValue($index->isUnique())
                    . ' != MIG: unique ' . $this->stringifyValue($oldIndex->isUnique()) . ')'
                );
                $blueprint->dropIndex($oldIndex);
                $blueprint->addIndex($index);

                continue;
            }

            $newIndexColumns = $index->getColumns();
            $oldIndexColumns = $oldIndex->getColumns();
            $intersection = array_intersect($newIndexColumns, $oldIndexColumns);

            if (
                count(
                    array_merge(
                        array_diff($newIndexColumns, $intersection),
                        array_diff($oldIndexColumns, $intersection)
                    )
                )
            ) {
                $blueprint->addDescription(
                    "different index '$name' columns (DB: "
                    . $this->stringifyValue($newIndexColumns) . ') != MIG: ('
                    . $this->stringifyValue($oldIndexColumns) . '))'
                );
                $blueprint->dropIndex($oldIndex);
                $blueprint->addIndex($index);
            }
        }

        foreach ($oldIndexes as $name => $index) {
            if (array_key_exists($name, $newIndexes) === false) {
                $blueprint->addDescription("excessive index '$name'");
                $blueprint->dropIndex($index);
            }
        }
    }

    /**
     * Checks if append statements are the same in new and old structure.
     * Compares the actual statements and potential ones.
     * @param ColumnInterface $newColumn
     * @param ColumnInterface $oldColumn
     * @return bool
     */
    private function isAppendSame(ColumnInterface $newColumn, ColumnInterface $oldColumn): bool
    {
        [$newPrimaryKey, $newAutoincrement, $newAppend] = $this->stripAppend($newColumn->getAppend());
        if ($newPrimaryKey === false && $newColumn->isPrimaryKey()) {
            $newPrimaryKey = true;
        }
        if ($newAutoincrement === false && $newColumn->isAutoIncrement()) {
            $newAutoincrement = true;
        }

        [$oldPrimaryKey, $oldAutoincrement, $oldAppend] = $this->stripAppend($oldColumn->getAppend());
        if ($oldPrimaryKey === false && $oldColumn->isPrimaryKey()) {
            $oldPrimaryKey = true;
        }
        if ($oldAutoincrement === false && $oldColumn->isAutoIncrement()) {
            $oldAutoincrement = true;
        }

        return $newAppend === $oldAppend
            && $newPrimaryKey === $oldPrimaryKey
            && $newAutoincrement === $oldAutoincrement;
    }

    /**
     * Strips append string from primary key and autoincrement constraints.
     * @param string|null $append
     * @return array<string|bool|null>
     */
    private function stripAppend(?string $append): array
    {
        $autoIncrement = false;
        $primaryKey = false;

        if ($append !== null) {
            if (strpos($append, 'AUTO_INCREMENT') !== false) {
                $autoIncrement = true;
                $append = trim(str_replace('AUTO_INCREMENT', '', $append));
            }

            if (strpos($append, 'AUTOINCREMENT') !== false) {
                $autoIncrement = true;
                $append = trim(str_replace('AUTOINCREMENT', '', $append));
            }

            if (strpos($append, 'IDENTITY PRIMARY KEY') !== false) {
                $primaryKey = true;
                $append = trim(str_replace('IDENTITY PRIMARY KEY', '', $append));
            }

            if (strpos($append, 'PRIMARY KEY') !== false) {
                $primaryKey = true;
                $append = trim(str_replace('PRIMARY KEY', '', $append));
            }

            /** @var string $append */
            $append = str_replace(' ', '', $append);
            if ($append === '') {
                $append = null;
            }
        }

        return [$primaryKey, $autoIncrement, $append];
    }

    /**
     * Checks if columns' uniqueness is the same because of the unique index.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param string $columnName
     * @param bool $newUnique
     * @param bool $oldUnique
     * @return bool
     */
    private function isUniqueSameWithIndexes(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        string $columnName,
        bool $newUnique,
        bool $oldUnique
    ): bool {
        if ($newUnique) {
            $newIndexes = $newStructure->getIndexes();
            /** @var IndexInterface $newIndex */
            foreach ($newIndexes as $newIndex) {
                $indexColumns = $newIndex->getColumns();
                if ($newIndex->isUnique() && count($indexColumns) === 1 && in_array($columnName, $indexColumns, true)) {
                    $newUnique = false;
                    break;
                }
            }
        }

        if ($oldUnique) {
            $oldIndexes = $oldStructure->getIndexes();
            /** @var IndexInterface $oldIndex */
            foreach ($oldIndexes as $oldIndex) {
                $indexColumns = $oldIndex->getColumns();
                if ($oldIndex->isUnique() && count($indexColumns) === 1 && in_array($columnName, $indexColumns, true)) {
                    $oldUnique = false;
                    break;
                }
            }
        }

        return $newUnique === $oldUnique;
    }

    /**
     * Returns values as strings.
     * @param mixed $value
     * @return string
     */
    private function stringifyValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value === true) {
            return 'TRUE';
        }

        if ($value === false) {
            return 'FALSE';
        }

        if (is_array($value)) {
            return Json::encode($value);
        }

        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
