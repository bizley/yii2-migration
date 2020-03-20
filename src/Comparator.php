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
    /** @var bool */
    private $generalSchema;

    /** @var BlueprintInterface */
    private $blueprint;

    public function __construct(bool $generalSchema)
    {
        $this->generalSchema = $generalSchema;
    }

    public function setBlueprint(BlueprintInterface $blueprint): void
    {
        $this->blueprint = $blueprint;
    }

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow whether changes should be only displayed
     * @param string|null $schema
     * @param string|null $engineVersion
     * @throws NotSupportedException
     */
    public function compare(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void {
        $this->compareColumns($newStructure, $oldStructure, $onlyShow, $schema, $engineVersion);
        $this->compareForeignKeys($newStructure, $oldStructure, $onlyShow, $schema);
        $this->comparePrimaryKeys($newStructure->getPrimaryKey(), $oldStructure->getPrimaryKey(), $onlyShow, $schema);
        $this->compareIndexes($newStructure, $oldStructure, $onlyShow);
    }

    /**
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow
     * @param string|null $schema
     * @param string|null $engineVersion
     * @throws NotSupportedException
     */
    private function compareColumns(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void {
        $previousColumn = null;
        $newColumns = $newStructure->getColumns();
        $newPrimaryKey = $newStructure->getPrimaryKey();
        $oldColumns = $oldStructure->getColumns();

        /** @var ColumnInterface $column */
        foreach ($newColumns as $name => $column) {
            if (array_key_exists($name, $oldColumns) === false) {
                $this->blueprint->addDescription("missing column '$name'");
                if ($previousColumn) {
                    $column->setAfter($previousColumn);
                } else {
                    $column->setFirst(true);
                }
                $this->blueprint->addColumn($column);

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
                $column->setAppend($column->prepareSchemaAppend($schema, true, $column->isAutoIncrement()));
            }

            $previousColumn = $name;
            foreach (
                [
                    'getType',
                    'isNotNull',
                    'getLength',
                    'isUnique',
                    'isUnsigned',
                    'getDefault',
                    'getAppend',
                    'getComment',
                ] as $propertyFetch
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
                    if (
                        $propertyFetch === 'getAppend'
                        && $oldProperty === null
                        && $this->isAppendSame($newProperty, $oldColumn)
                    ) {
                        continue;
                    }

                    $this->blueprint->addDescription(
                        "different '$name' column property: $propertyFetch ("
                        . 'DB: ' . $this->stringifyValue($newProperty) . ' != '
                        . 'MIG: ' . $this->stringifyValue($oldProperty) . ')'
                    );

                    if ($schema === Schema::SQLITE) {
                        $this->blueprint->addDescription(
                            '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually'
                        );
                        if ($onlyShow === false) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }
                    }
                    $this->blueprint->alterColumn($column);
                }
            }
        }

        /** @var ColumnInterface $column */
        foreach ($oldColumns as $name => $column) {
            if (array_key_exists($name, $newColumns) === false) {
                $this->blueprint->addDescription("excessive column '$name'");
                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP COLUMN is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }
                }

                $this->blueprint->dropColumn($name);
            }
        }
    }

    /**
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow
     * @param string|null $schema
     * @throws NotSupportedException
     */
    private function compareForeignKeys(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow,
        ?string $schema
    ): void {
        $newForeignKeys = $newStructure->getForeignKeys();
        $oldForeignKeys = $oldStructure->getForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($newForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $oldForeignKeys) === false) {
                $this->blueprint->addDescription("missing foreign key '$name'");

                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->addForeignKey($foreignKey);

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
                $this->blueprint->addDescription(
                    "different foreign key '$name' columns ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyColumns) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyColumns) . ')'
                );

                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->dropForeignKey($name);
                $this->blueprint->addForeignKey($foreignKey);

                continue;
            }

            $newForeignKeyReferencedColumns = $foreignKey->getReferencedColumns();
            $oldForeignKeyReferencedColumns = $oldForeignKey->getReferencedColumns();
            $intersection = array_intersect($newForeignKeyReferencedColumns, $oldForeignKeyReferencedColumns);

            if (
                count(
                    array_merge(
                        array_diff($newForeignKeyReferencedColumns, $intersection),
                        array_diff($oldForeignKeyReferencedColumns, $intersection)
                    )
                )
            ) {
                $this->blueprint->addDescription(
                    "different foreign key '$name' referral columns ("
                    . 'DB: ' . $this->stringifyValue($newForeignKeyReferencedColumns) . ' != '
                    . 'MIG: ' . $this->stringifyValue($oldForeignKeyReferencedColumns) . ')'
                );

                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->dropForeignKey($name);
                $this->blueprint->addForeignKey($foreignKey);
            }
        }

        /** @var ForeignKeyInterface $foreignKey */
        foreach ($oldForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $newForeignKeys) === false) {
                $this->blueprint->addDescription("excessive foreign key '$name'");

                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->dropForeignKey($name);
            }
        }
    }

    /**
     * @param PrimaryKeyInterface|null $newPrimaryKey
     * @param PrimaryKeyInterface|null $oldPrimaryKey
     * @param bool $onlyShow
     * @param string|null $schema
     * @throws NotSupportedException
     */
    private function comparePrimaryKeys(
        ?PrimaryKeyInterface $newPrimaryKey,
        ?PrimaryKeyInterface $oldPrimaryKey,
        bool $onlyShow,
        ?string $schema
    ): void {
        $newPrimaryKeyColumns = $newPrimaryKey ? $newPrimaryKey->getColumns() : [];
        $oldPrimaryKeyColumns = $oldPrimaryKey ? $oldPrimaryKey->getColumns() : [];
        $intersection = array_intersect($newPrimaryKeyColumns, $oldPrimaryKeyColumns);

        $differentColumns = array_merge(
            array_diff($newPrimaryKeyColumns, $intersection),
            array_diff($oldPrimaryKeyColumns, $intersection)
        );

        if (count($differentColumns)) {
            $this->blueprint->addDescription('different primary key definition');

            if (count($oldPrimaryKeyColumns)) {
                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP PRIMARY KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->dropPrimaryKey($oldPrimaryKey->getName());
            }

            $newPrimaryKeyColumnsCount = count($newPrimaryKeyColumns);
            if ($this->shouldPrimaryKeyBeAdded($differentColumns, $newPrimaryKeyColumnsCount, $schema)) {
                $this->removeExcessivePrimaryKeyStatements($differentColumns, $newPrimaryKeyColumnsCount, $schema);

                if ($schema === Schema::SQLITE) {
                    $this->blueprint->addDescription(
                        '(!) DROP/ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually'
                    );
                    if ($onlyShow === false) {
                        throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
                    }
                }

                $this->blueprint->addPrimaryKey($newPrimaryKey);
            }
        }
    }

    /**
     * @param array $differentColumns
     * @param int $newPrimaryKeyColumnsCount
     * @param string|null $schema
     */
    private function removeExcessivePrimaryKeyStatements(
        array $differentColumns,
        int $newPrimaryKeyColumnsCount,
        ?string $schema
    ): void {
        if ($newPrimaryKeyColumnsCount > 1) {
            $addedColumns = $this->blueprint->getAddedColumns();
            $alteredColumns = $this->blueprint->getAlteredColumns();
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
     * @param array $differentColumns
     * @param int $columnsCount
     * @param string|null $schema
     * @return bool
     */
    private function shouldPrimaryKeyBeAdded(array $differentColumns, int $columnsCount, ?string $schema): bool
    {
        if ($columnsCount === 1 && count($differentColumns) === 1) {
            $addedColumns = $this->blueprint->getAddedColumns();
            /** @var ColumnInterface $column */
            foreach ($addedColumns as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($schema)) {
                    return false;
                }
            }

            $alteredColumns = $this->blueprint->getAlteredColumns();
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
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow
     */
    private function compareIndexes(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow
    ): void {
        $newIndexes = $newStructure->getIndexes();
        $oldIndexes = $oldStructure->getIndexes();

        /** @var IndexInterface $index */
        foreach ($newIndexes as $name => $index) {
            if (array_key_exists($name, $oldIndexes) === false) {
                if ($onlyShow) {
                    $this->blueprint->addDescription("missing index '$name'");
                } else {
                    $this->blueprint->createIndex($index);
                }

                continue;
            }

            /** @var IndexInterface $oldIndex */
            $oldIndex = $oldStructure->getIndex($name);
            if ($oldIndex->isUnique() !== $index->isUnique()) {
                $this->blueprint->addDescription(
                    "different index '$name' definition (DB: unique "
                    . $this->stringifyValue($index->isUnique())
                    . ' != MIG: unique ' . $this->stringifyValue($oldIndex->isUnique()) . ')'
                );
                $this->blueprint->dropIndex($name);
                $this->blueprint->createIndex($index);

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
                $this->blueprint->addDescription(
                    "different index '$name' columns (DB: "
                    . $this->stringifyValue($newIndexColumns) . ') != MIG: ('
                    . $this->stringifyValue($oldIndexColumns) . '))'
                );
                $this->blueprint->dropIndex($name);
                $this->blueprint->createIndex($index);
            }
        }

        foreach ($oldIndexes as $name => $index) {
            if (array_key_exists($name, $newIndexes) === false) {
                $this->blueprint->addDescription("excessive index '$name'");
                $this->blueprint->dropIndex($name);
            }
        }
    }

    private function isAppendSame(string $append, ColumnInterface $column): bool
    {
        $autoIncrement = false;
        $primaryKey = false;

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

        $append = str_replace(' ', '', $append);

        return $append === ''
            && $autoIncrement === $column->isAutoIncrement()
            && $primaryKey === $column->isPrimaryKey();
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
