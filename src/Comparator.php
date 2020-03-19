<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\StructureInterface;
use yii\base\NotSupportedException;
use yii\helpers\Json;

final class Comparator implements ComparatorInterface
{
    /** @var string|null */
    private $schema;

    /** @var string|null */
    private $engineVersion;

    /** @var bool */
    private $generalSchema;

    /** @var bool */
    private $isSchemaSQLite;

    /** @var BlueprintInterface */
    private $blueprint;

    public function __construct(
        BlueprintInterface $blueprint,
        ?string $schema,
        ?string $engineVersion,
        bool $generalSchema
    ) {
        $this->blueprint = $blueprint;
        $this->schema = $schema;
        $this->engineVersion = $engineVersion;
        $this->generalSchema = $generalSchema;

        $this->isSchemaSQLite = $schema === Schema::SQLITE;
    }

    /** @var array */
    private $differencesDescription = [];

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow whether changes should be only displayed
     * @throws NotSupportedException
     */
    public function compare(StructureInterface $newStructure, StructureInterface $oldStructure, bool $onlyShow): void
    {
        $this->compareColumns($newStructure, $oldStructure, $onlyShow);
        $this->compareForeignKeys($newStructure, $oldStructure, $onlyShow);
        $this->comparePrimaryKeys($newStructure->getPrimaryKey(), $oldStructure->getPrimaryKey(), $onlyShow);



        foreach ($this->getTableStructure()->indexes as $name => $index) {
            if (array_key_exists($name, $this->getOldStructure()->indexes) === false) {
                if ($this->showOnly) {
                    echo "   - missing index '$name'\n";
                } else {
                    $this->getPlan()->createIndex[$name] = $index;
                }

                $different = true;

                continue;
            }

            if ($this->getOldStructure()->indexes[$name]->unique !== $this->getTableStructure()->indexes[$name]->unique) {
                if ($this->showOnly) {
                    echo "   - different index '$name' definition (DB: unique "
                        . $this->stringifyValue($this->getTableStructure()->indexes[$name]->unique)
                        . ' <> MIG: unique ' . $this->stringifyValue($this->getOldStructure()->indexes[$name]->unique)
                        . ")\n";
                } else {
                    $this->getPlan()->dropIndex[] = $name;
                    $this->getPlan()->createIndex[$name] = $index;
                }

                $different = true;

                continue;
            }

            $tableIndexColumns
                = !empty($this->getTableStructure()->indexes[$name]->columns)
                ? $this->getTableStructure()->indexes[$name]->columns
                : [];
            $oldTableIndexColumns
                = !empty($this->getOldStructure()->indexes[$name]->columns)
                ? $this->getOldStructure()->indexes[$name]->columns
                : [];

            if (
            count(
                array_merge(
                    array_diff($tableIndexColumns, array_intersect($tableIndexColumns, $oldTableIndexColumns)),
                    array_diff($oldTableIndexColumns, array_intersect($tableIndexColumns, $oldTableIndexColumns))
                )
            )
            ) {
                if ($this->showOnly) {
                    echo "   - different index '$name' columns (DB: ("
                        . implode(', ', $tableIndexColumns)
                        . ') <> MIG: ('
                        . implode(', ', $oldTableIndexColumns)
                        . "))\n";
                } else {
                    $this->getPlan()->dropIndex[] = $name;
                    $this->getPlan()->createIndex[$name] = $index;
                }

                $different = true;
            }
        }

        foreach ($this->getOldStructure()->indexes as $name => $index) {
            if (array_key_exists($name, $this->getTableStructure()->indexes) === false) {
                if ($this->showOnly) {
                    echo "   - excessive index '$name'\n";
                } else {
                    $this->getPlan()->dropIndex[] = $name;
                }

                $different = true;
            }
        }

        return $different;
    }

    /**
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow
     * @throws NotSupportedException
     */
    private function compareColumns(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow
    ): void {
        $previousColumn = null;
        $newColumns = $newStructure->getColumns();
        $newPrimaryKey = $newStructure->getPrimaryKey();
        $oldColumns = $oldStructure->getColumns();

        /** @var ColumnInterface $column */
        foreach ($newColumns as $name => $column) {
            if (array_key_exists($name, $oldColumns) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "missing column '$name'";
                } else {
                    if ($previousColumn) {
                        $column->setAfter($previousColumn);
                    } else {
                        $column->setFirst(true);
                    }
                    $this->blueprint->addColumn($name, $column);
                }

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
                $column->setAppend($column->prepareSchemaAppend($this->schema, true, $column->isAutoIncrement()));
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
                $oldColumn = $oldStructure->getColumn($name);
                if ($propertyFetch === 'getLength') {
                    $oldProperty = $oldColumn->getLength($this->schema, $this->engineVersion);
                    $newProperty = $column->getLength($this->schema, $this->engineVersion);
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

                    if ($onlyShow) {
                        $this->differencesDescription[] = "different '$name' column property: $propertyFetch ("
                            . 'DB: ' . $this->stringifyValue($newProperty) . ' != '
                            . 'MIG: ' . $this->stringifyValue($oldProperty) . ')';

                        if ($this->isSchemaSQLite) {
                            $this->differencesDescription[]
                                = '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually';
                        }
                    } else {
                        if ($this->isSchemaSQLite) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }

                        $this->blueprint->alterColumn($name, $column);
                    }
                }
            }
        }

        /** @var ColumnInterface $column */
        foreach ($oldColumns as $name => $column) {
            if (array_key_exists($name, $newColumns) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "excessive column '$name'";

                    if ($this->isSchemaSQLite) {
                        $this->differencesDescription[]
                            = '(!) DROP COLUMN is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }

                    $this->blueprint->dropColumn($name);
                }
            }
        }
    }

    /**
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param bool $onlyShow
     * @throws NotSupportedException
     */
    private function compareForeignKeys(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        bool $onlyShow
    ): void {
        $newForeignKeys = $newStructure->getForeignKeys();
        $oldForeignKeys = $oldStructure->getForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($newForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $oldForeignKeys) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "missing foreign key '$name'";

                    if ($this->isSchemaSQLite) {
                        $this->differencesDescription[]
                            = '(!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->addForeignKey($name, $foreignKey);
                }

                continue;
            }

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
                if ($onlyShow) {
                    $this->differencesDescription[] = "different foreign key '$name' columns ("
                        . 'DB: ' . $this->stringifyValue($newForeignKeyColumns) . ' != '
                        . 'MIG: ' . $this->stringifyValue($oldForeignKeyColumns) . ')';

                    if ($this->isSchemaSQLite) {
                        $this->differencesDescription[]
                            = '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                    $this->blueprint->addForeignKey($name, $foreignKey);
                }

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
                if ($onlyShow) {
                    $this->differencesDescription[] = "different foreign key '$name' referral columns ("
                        . 'DB: ' . $this->stringifyValue($newForeignKeyReferencedColumns) . ' != '
                        . 'MIG: ' . $this->stringifyValue($oldForeignKeyReferencedColumns) . ')';

                    if ($this->isSchemaSQLite) {
                        $this->differencesDescription[]
                            = '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                    $this->blueprint->addForeignKey($name, $foreignKey);
                }
            }
        }

        /** @var ForeignKeyInterface $foreignKey */
        foreach ($oldForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $newForeignKeys) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "excessive foreign key '$name'";

                    if ($this->isSchemaSQLite) {
                        $this->differencesDescription[]
                            = '(!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                }
            }
        }
    }

    /**
     * @param PrimaryKeyInterface|null $newPrimaryKey
     * @param PrimaryKeyInterface|null $oldPrimaryKey
     * @param bool $onlyShow
     * @throws NotSupportedException
     */
    private function comparePrimaryKeys(
        ?PrimaryKeyInterface $newPrimaryKey,
        ?PrimaryKeyInterface $oldPrimaryKey,
        bool $onlyShow
    ): void {
        $newPrimaryKeyColumns = $newPrimaryKey ? $newPrimaryKey->getColumns() : [];
        $oldPrimaryKeyColumns = $oldPrimaryKey ? $oldPrimaryKey->getColumns() : [];
        $intersection = array_intersect($newPrimaryKeyColumns, $oldPrimaryKeyColumns);

        $differentColumns = array_merge(
            array_diff($newPrimaryKeyColumns, $intersection),
            array_diff($oldPrimaryKeyColumns, $intersection)
        );

        if (count($differentColumns)) {
            if ($onlyShow) {
                $this->differencesDescription[] = 'different primary key definition';

                if ($this->isSchemaSQLite) {
                    $this->differencesDescription[]
                        = '(!) DROP/ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually';
                }
            } else {
                if (count($oldPrimaryKeyColumns)) {
                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropPrimaryKey($oldPrimaryKey->getName());
                }

                $newPrimaryKeyColumnsCount = count($newPrimaryKeyColumns);
                if ($this->shouldPrimaryKeyBeAdded($differentColumns, $newPrimaryKeyColumnsCount)) {
                    $this->removeExcessivePrimaryKeyStatements($differentColumns, $newPrimaryKeyColumnsCount);

                    if ($this->isSchemaSQLite) {
                        throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->blueprint->addPrimaryKey($newPrimaryKey);
                }
            }
        }
    }

    /**
     * @param array $differentColumns
     * @param int $newPrimaryKeyColumnsCount
     */
    private function removeExcessivePrimaryKeyStatements(
        array $differentColumns,
        int $newPrimaryKeyColumnsCount
    ): void {
        if ($newPrimaryKeyColumnsCount > 1) {
            $addedColumns = $this->blueprint->getAddedColumns();
            $alteredColumns = $this->blueprint->getAlteredColumns();
            foreach ($differentColumns as $differentColumn) {
                /** @var ColumnInterface $column */
                foreach ($addedColumns as $name => $column) {
                    if ($name === $differentColumn) {
                        $column->setAppend($column->removeAppendedPrimaryKeyInfo($this->schema));
                        break;
                    }
                }

                foreach ($alteredColumns as $name => $column) {
                    if ($name === $differentColumn) {
                        $column->setAppend($column->removeAppendedPrimaryKeyInfo($this->schema));
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param array $differentColumns
     * @param int $columnsCount
     * @return bool
     */
    private function shouldPrimaryKeyBeAdded(array $differentColumns, int $columnsCount): bool
    {
        if ($columnsCount === 1 && count($differentColumns) === 1) {
            $addedColumns = $this->blueprint->getAddedColumns();
            /** @var ColumnInterface $column */
            foreach ($addedColumns as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($this->schema)) {
                    return false;
                }
            }

            $alteredColumns = $this->blueprint->getAlteredColumns();
            foreach ($alteredColumns as $name => $column) {
                if ($name === $differentColumns[0] && $column->isPrimaryKeyInfoAppended($this->schema)) {
                    return false;
                }
            }

            return true;
        }

        return true;
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
