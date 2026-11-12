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

final class Comparator implements ComparatorInterface
{
    public function __construct(private readonly bool $generalSchema)
    {
    }

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param bool $onlyShow whether changes should be only displayed
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
            if (!\array_key_exists($name, $oldColumns)) {
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
                && !$newPrimaryKey->isComposite()
                && $column->isColumnInPrimaryKey($newPrimaryKey)
            ) {
                $column->setAppend($column->prepareSchemaAppend(true, $column->isAutoIncrement(), $schema));
            }

            $previousColumn = $name;
            /** @var ColumnInterface $oldColumn */
            $oldColumn = $oldStructure->getColumn($name);
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
                if ($propertyFetch === 'getLength') {
                    $oldProperty = $oldColumn->getLength($schema, $engineVersion);
                    $newProperty = $column->getLength($schema, $engineVersion);
                } else {
                    $oldProperty = $oldColumn->$propertyFetch();
                    $newProperty = $column->$propertyFetch();
                }
                if (!\is_bool($oldProperty) && $oldProperty !== null && !\is_array($oldProperty)) {
                    $oldProperty = (string)$oldProperty;
                }
                if (!\is_bool($newProperty) && $newProperty !== null && !\is_array($newProperty)) {
                    $newProperty = (string)$newProperty;
                }
                if ($oldProperty !== $newProperty) {
                    if ($propertyFetch === 'getLength' && $this->isLengthSame($newProperty, $oldProperty)) {
                        continue;
                    }

                    if ($propertyFetch === 'getAppend' && $this->isAppendSame($column, $oldColumn)) {
                        continue;
                    }

                    if (
                        $propertyFetch === 'isUnique'
                        && $this->getRealUniqueness($newStructure, $name, (bool)$newProperty)
                            === $this->getRealUniqueness($oldStructure, $name, (bool)$oldProperty)
                    ) {
                        continue;
                    }

                    if (
                        $propertyFetch === 'getDefault'
                        && $schema === Schema::SQLITE
                        && $newProperty === 'NULL'
                        && $oldProperty === null
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

        foreach ($oldColumns as $name => $column) {
            if (!\array_key_exists($name, $newColumns)) {
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
        foreach ($newForeignKeys as $name => $foreignKey) {
            if (!\array_key_exists($name, $oldForeignKeys)) {
                $blueprint->addDescription("missing foreign key '$name'");
                $this->checkSqliteSupport($blueprint, $onlyShow, $schema, 'ADD FOREIGN KEY');
                $blueprint->addForeignKey($foreignKey);

                continue;
            }

            /** @var ForeignKeyInterface $oldForeignKey */
            $oldForeignKey = $oldStructure->getForeignKey($name);
            $newForeignKeyColumns = $foreignKey->getColumns();
            $oldForeignKeyColumns = $oldForeignKey->getColumns();

            if ($this->areColumnSetsDifferent($newForeignKeyColumns, $oldForeignKeyColumns)) {
                $this->replaceForeignKey(
                    $blueprint,
                    $foreignKey,
                    $oldForeignKey,
                    "different foreign key '$name' columns ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyColumns) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyColumns) . ')',
                    $onlyShow,
                    $schema
                );

                continue;
            }

            $newReferredColumns = $foreignKey->getReferredColumns();
            $oldReferredColumns = $oldForeignKey->getReferredColumns();
            foreach (
                [
                    [
                        'referred columns',
                        $newReferredColumns,
                        $oldReferredColumns,
                        $this->areColumnSetsDifferent($newReferredColumns, $oldReferredColumns),
                    ],
                    [
                        'referred table',
                        $foreignKey->getReferredTable(),
                        $oldForeignKey->getReferredTable(),
                        $foreignKey->getReferredTable() !== $oldForeignKey->getReferredTable(),
                    ],
                    [
                        'ON UPDATE constraint',
                        $foreignKey->getOnUpdate(),
                        $oldForeignKey->getOnUpdate(),
                        $foreignKey->getOnUpdate() !== $oldForeignKey->getOnUpdate(),
                    ],
                    [
                        'ON DELETE constraint',
                        $foreignKey->getOnDelete(),
                        $oldForeignKey->getOnDelete(),
                        $foreignKey->getOnDelete() !== $oldForeignKey->getOnDelete(),
                    ],
                ] as [$property, $newValue, $oldValue, $isDifferent]
            ) {
                if ($isDifferent) {
                    $this->replaceForeignKey(
                        $blueprint,
                        $foreignKey,
                        $oldForeignKey,
                        "different foreign key '$name' $property ("
                        . 'DB: ' . $this->stringifyValue($newValue) . ' != '
                        . 'MIG: ' . $this->stringifyValue($oldValue) . ')',
                        $onlyShow,
                        $schema
                    );
                }
            }
        }

        foreach ($oldForeignKeys as $name => $foreignKey) {
            if (!\array_key_exists($name, $newForeignKeys)) {
                $blueprint->addDescription("excessive foreign key '$name'");
                $this->checkSqliteSupport($blueprint, $onlyShow, $schema, 'DROP FOREIGN KEY');
                $blueprint->dropForeignKey($foreignKey);
            }
        }
    }

    /**
     * Checks whether the lists of columns contain different columns (the order is not important).
     * @param array<string> $newColumns
     * @param array<string> $oldColumns
     */
    private function areColumnSetsDifferent(array $newColumns, array $oldColumns): bool
    {
        return \array_diff($newColumns, $oldColumns) !== \array_diff($oldColumns, $newColumns);
    }

    /**
     * Adds the description of the foreign key difference and replaces the old foreign key with the new one.
     * @throws NotSupportedException
     */
    private function replaceForeignKey(
        BlueprintInterface $blueprint,
        ForeignKeyInterface $newForeignKey,
        ForeignKeyInterface $oldForeignKey,
        string $description,
        bool $onlyShow,
        ?string $schema
    ): void {
        $blueprint->addDescription($description);
        $this->checkSqliteSupport($blueprint, $onlyShow, $schema, 'DROP/ADD FOREIGN KEY');
        $blueprint->dropForeignKey($oldForeignKey);
        $blueprint->addForeignKey($newForeignKey);
    }

    /**
     * Adds the warning when the operation is not supported by SQLite and throws an exception if the changes are not
     * only being displayed.
     * @throws NotSupportedException
     */
    private function checkSqliteSupport(
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema,
        string $operation
    ): void {
        if ($schema === Schema::SQLITE) {
            $blueprint->addDescription(
                "(!) $operation is not supported by SQLite: Migration must be created manually"
            );
            if ($onlyShow === false) {
                throw new NotSupportedException("$operation is not supported by SQLite.");
            }
        }
    }

    /**
     * Compares the primary keys between new and old structure.
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
        $differentColumns = \array_diff($newPrimaryKeyColumns, $oldPrimaryKeyColumns);

        if ($differentColumns !== \array_diff($oldPrimaryKeyColumns, $newPrimaryKeyColumns)) {
            $blueprint->addDescription('different primary key definition');

            $alreadyDropped = false;
            if (!empty($oldPrimaryKeyColumns)) {
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

            $newPrimaryKeyColumnsCount = \count($newPrimaryKeyColumns);
            if ($this->shouldPrimaryKeyBeAdded($blueprint, $differentColumns, $newPrimaryKeyColumnsCount, $schema)) {
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

                $this->removeExcessivePrimaryKeyStatements($blueprint, $schema);

                /** @var PrimaryKeyInterface $newPrimaryKey */
                $blueprint->addPrimaryKey($newPrimaryKey);
            }
        }
    }

    /**
     * Removes excessive primary key statements from the column in case the primary key will be added separately anyway.
     */
    private function removeExcessivePrimaryKeyStatements(BlueprintInterface $blueprint, ?string $schema): void
    {
        foreach ($blueprint->getAddedColumns() as $column) {
            $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
        }

        foreach ($blueprint->getAlteredColumns() as $column) {
            $column->setAppend($column->removeAppendedPrimaryKeyInfo($schema));
        }
    }

    /**
     * Checks whether the separate primary key needs to be added.
     * @param array<string> $differentColumns
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
        if ($newColumnsCount === 1 && \count($differentColumns) === 1) {
            foreach ($blueprint->getAddedColumns() as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($schema)) {
                    return false;
                }
            }

            foreach ($blueprint->getAlteredColumns() as $name => $column) {
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
     */
    private function compareIndexes(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint
    ): void {
        $newIndexes = $newStructure->getIndexes();
        $oldIndexes = $oldStructure->getIndexes();

        foreach ($newIndexes as $name => $index) {
            if (!\array_key_exists($name, $oldIndexes)) {
                $indexColumns = $index->getColumns();
                if (
                    $index->isUnique()
                    && \count($indexColumns) === 1
                    && ($newIndexColumn = $newStructure->getColumn($indexColumns[0]))
                    && $newIndexColumn->isUnique()
                    && ($oldIndexColumn = $oldStructure->getColumn($indexColumns[0]))
                    && $oldIndexColumn->isUnique()
                ) {
                    // index is created for one unique column and this uniqueness has not changed
                    continue;
                }

                foreach ($newStructure->getForeignKeys() as $foreignKey) {
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

            if ($oldIndex->getMethod() !== $index->getMethod()) {
                $blueprint->addDescription(
                    "different index '$name' definition (DB: method "
                    . ($index->getMethod() ?? 'default')
                    . ' != MIG: method ' . ($oldIndex->getMethod() ?? 'default') . ')'
                );
                $blueprint->dropIndex($oldIndex);
                $blueprint->addIndex($index);

                continue;
            }

            $newIndexColumns = $index->getColumns();
            $oldIndexColumns = $oldIndex->getColumns();

            // order of columns matters for the index
            if (\array_values($newIndexColumns) !== \array_values($oldIndexColumns)) {
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
            if (!\array_key_exists($name, $newIndexes)) {
                $blueprint->addDescription("excessive index '$name'");
                $blueprint->dropIndex($index);
            }
        }
    }

    /**
     * Checks if append statements are the same in new and old structure.
     * Compares the actual statements and potential ones.
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
     * @return array<string|bool|null>
     */
    private function stripAppend(?string $append): array
    {
        $autoIncrement = false;
        $primaryKey = false;

        if ($append !== null) {
            if (\str_contains($append, 'AUTO_INCREMENT')) {
                $autoIncrement = true;
                $append = \str_replace('AUTO_INCREMENT', '', $append);
            }

            if (\str_contains($append, 'AUTOINCREMENT')) {
                $autoIncrement = true;
                $append = \str_replace('AUTOINCREMENT', '', $append);
            }

            if (\str_contains($append, 'IDENTITY PRIMARY KEY')) {
                $primaryKey = true;
                $append = \str_replace('IDENTITY PRIMARY KEY', '', $append);
            }

            if (\str_contains($append, 'PRIMARY KEY')) {
                $primaryKey = true;
                $append = \str_replace('PRIMARY KEY', '', $append);
            }

            $append = \trim($append);
            if ($append === '') {
                $append = null;
            }
        }

        return [$primaryKey, $autoIncrement, $append];
    }

    /**
     * Checks if columns' uniqueness is the same because of the unique index.
     */
    private function getRealUniqueness(StructureInterface $structure, string $columnName, bool $unique): bool
    {
        if ($unique) {
            foreach ($structure->getIndexes() as $index) {
                $indexColumns = $index->getColumns();
                if ($index->isUnique() && \count($indexColumns) === 1 && \in_array($columnName, $indexColumns, true)) {
                    return false;
                }
            }
        }

        return $unique;
    }

    /**
     * Checks if length has the same value but written differently.
     * @param mixed $newLength
     * @param mixed $oldLength
     */
    private function isLengthSame($newLength, $oldLength): bool
    {
        $normalizedNew = $newLength;
        if (
            \is_string($newLength)
            && ($commaPosition = \strpos($newLength, ',')) !== false
            && \preg_match('/,\s?0$/', $newLength)
        ) {
            $normalizedNew = substr($newLength, 0, $commaPosition);
        }
        $normalizedOld = $oldLength;
        if (
            \is_string($oldLength)
            && ($commaPosition = \strpos($oldLength, ',')) !== false
            && \preg_match('/,\s?0$/', $oldLength)) {
            $normalizedOld = \substr($oldLength, 0, $commaPosition);
        }
        return $normalizedNew === $normalizedOld;
    }

    /**
     * Returns values as strings.
     * @param mixed $value
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

        if (\is_array($value)) {
            return Json::encode($value);
        }

        return '"' . \str_replace('"', '\"', $value) . '"';
    }
}
