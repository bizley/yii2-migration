<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\Blueprint;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\Structure;
use bizley\migration\table\StructureBuilder;
use bizley\migration\table\StructureBuilderInterface;
use bizley\migration\table\StructureChange;
use bizley\migration\table\StructureInterface;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use yii\helpers\Json;

use function array_key_exists;
use function array_reverse;
use function count;
use function implode;
use function in_array;
use function is_array;
use function str_replace;
use function strpos;
use function trim;

class Inspector implements InspectorInterface
{
    /** @var HistoryManagerInterface */
    private $historyManager;

    /** @var ExtractorInterface */
    private $extractor;

    /** @var StructureInterface */
    private $newStructure;

    /** @var StructureBuilderInterface */
    private $structureBuilder;

    public function __construct(
        HistoryManagerInterface $historyManager,
        ExtractorInterface $extractor,
        StructureInterface $newStructure,
        StructureBuilderInterface $structureBuilder
    ) {
        $this->historyManager = $historyManager;
        $this->extractor = $extractor;
        $this->newStructure = $newStructure;
        $this->structureBuilder = $structureBuilder;
    }

    /** @var string */
    private $currentTable;

    /**
     * @param string $tableName
     * @param bool $onlyShow
     * @param array $migrationsToSkip
     * @param array $migrationPaths
     * @return bool
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function isUpdateRequired(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip = [],
        array $migrationPaths = []
    ): bool {
        $this->currentTable = $tableName;
        $history = $this->historyManager->fetchHistory();

        if (count($history)) {
            foreach ($history as $migration => $time) {
                $migration = trim($migration, '\\');
                if (in_array($migration, $migrationsToSkip, true)) {
                    continue;
                }

                $this->extractor->extract($migration, $migrationPaths);

                if ($this->gatherChanges($this->extractor->getChanges()) === false) {
                    break;
                }
            }

            return $this->compareStructures($onlyShow);
        }

        return true;
    }

    /** @var array<StructureChange> */
    private $appliedChanges = [];

    /**
     * @param array<StructureChange> $changes
     * @return bool true if more data can be analysed or false if this must be last one
     * @throws InvalidConfigException
     */
    private function gatherChanges(array $changes): bool
    {
        if (array_key_exists($this->currentTable, $changes) === false) {
            return true;
        }

        $data = array_reverse($changes[$this->currentTable]);


        /** @var StructureChange $change */
        foreach ($data as $change) {
            $method = $change->getMethod();

            if ($method === 'dropTable') {
                return false;
            }

            if ($method === 'renameTable') {
                $this->currentTable = $change->getValue();
                return $this->gatherChanges($changes);
            }

            $this->appliedChanges[] = $change;

            if ($method === 'createTable') {
                return false;
            }
        }

        return true;
    }

    /** @var array */
    private $differencesDescription = [];

    /** @var BlueprintInterface */
    private $blueprint;

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param bool $onlyShow whether changes should be only displayed
     * @return bool whether modification is required or not
     * @throws NotSupportedException
     */
    private function compareStructures(bool $onlyShow): bool
    {
        if (count($this->appliedChanges) === 0) {
            return true;
        }

        $different = false;
        $previousColumn = null;
        $newColumns = $this->newStructure->getColumns();
        $oldColumns = $this->getOldStructure()->getColumns();
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

                $different = true;
                $previousColumn = $name;

                continue;
            }

            $previousColumn = $name;

            foreach (
                ['type', 'isNotNull', 'length', 'isUnique', 'isUnsigned', 'default', 'append', 'comment'] as $property
            ) {
                if (
                    $this->generalSchema === false
                    && $property === 'append'
                    && $column->append === null
                    && $this->newStructure->getPrimaryKey()->isComposite() === false
                    && $column->isColumnInPrimaryKey($this->newStructure->getPrimaryKey())
                ) {
                    $column->setAppend($column->prepareSchemaAppend(true, $column->isAutoIncrement()));
                }

                $oldProperty = $this->getOldStructure()->getColumn($name)->$property;
                if (is_bool($oldProperty) === false && $oldProperty !== null && is_array($oldProperty) === false) {
                    $oldProperty = (string)$oldProperty;
                }
                $newProperty = $column->$property;
                if (is_bool($newProperty) === false && $newProperty !== null && is_array($newProperty) === false) {
                    $newProperty = (string)$newProperty;
                }
                if ($oldProperty !== $newProperty) {
                    if (
                        $property === 'append'
                        && $oldProperty === null
                        && $this->isAppendSame($newProperty, $this->getOldStructure()->getColumn($name))
                    ) {
                        continue;
                    }

                    if ($onlyShow) {
                        $this->differencesDescription[] = "different '$name' column property: $property ("
                            . 'DB: ' . $this->displayValue($newProperty) . ' != '
                            . 'MIG: ' . $this->displayValue($oldProperty) . ')';

                        if ($this->getTableStructure()->isSchemaSQLite()) {
                            $this->differencesDescription[] = '(!) ALTER COLUMN is not supported by SQLite: Migration must be created manually';
                        }
                    } else {
                        if ($this->getTableStructure()->isSchemaSQLite()) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }

                        $this->blueprint->alterColumn($name, $column);
                    }

                    $different = true;
                }
            }
        }


        /** @var ColumnInterface $column */
        foreach ($oldColumns as $name => $column) {
            if (array_key_exists($name, $newColumns) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "excessive column '$name'";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        $this->differencesDescription[] = '(!) DROP COLUMN is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }

                    $this->blueprint->dropColumn($name);
                }

                $different = true;
            }
        }

        $newForeignKeys = $this->newStructure->getForeignKeys();
        $oldForeignKeys = $this->getOldStructure()->getForeignKeys();
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($newForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $oldForeignKeys) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "missing foreign key '$name'";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->addForeignKey($name, $foreignKey);
                }

                $different = true;

                continue;
            }

            $newForeignKeyColumns = $foreignKey->getColumns();
            $oldForeignKeyColumns = $this->getOldStructure()->getForeignKey($name)->getColumns();
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
                        . 'DB: ' . $this->displayValue($newForeignKeyColumns) . ' != '
                        . 'MIG: ' . $this->displayValue($oldForeignKeyColumns) . ')';

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        $this->differencesDescription[] = '(!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                    $this->blueprint->addForeignKey($name, $foreignKey);
                }

                $different = true;

                continue;
            }

            $newForeignKeyReferencedColumns = $foreignKey->getReferencedColumns();
            $oldForeignKeyReferencedColumns = $this->getOldStructure()->getForeignKey($name)->getReferencedColumns();
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
                        . 'DB: ' . $this->displayValue($newForeignKeyReferencedColumns) . ' != '
                        . 'MIG: ' . $this->displayValue($oldForeignKeyReferencedColumns) . ')';

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                    $this->blueprint->addForeignKey($name, $foreignKey);
                }

                $different = true;
            }
        }

        /** @var ForeignKeyInterface $foreignKey */
        foreach ($oldForeignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $newForeignKeys) === false) {
                if ($onlyShow) {
                    $this->differencesDescription[] = "excessive foreign key '$name'";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        $this->differencesDescription[] = '(!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually';
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropForeignKey($name);
                }

                $different = true;
            }
        }

        $newPrimaryKey = $this->newStructure->getPrimaryKey();
        $newPrimaryKeyColumns = $newPrimaryKey ? $newPrimaryKey->getColumns() : [];
        $oldPrimaryKey = $this->getOldStructure()->getPrimaryKey();
        $oldPrimaryKeyColumns = $oldPrimaryKey ? $oldPrimaryKey->getColumns() : [];
        $intersection = array_intersect($newPrimaryKeyColumns, $oldPrimaryKeyColumns);

        $newKeys = array_merge(
            array_diff($newPrimaryKeyColumns, $intersection),
            array_diff($oldPrimaryKeyColumns, $intersection)
        );

        if (count($newKeys)) {
            if ($onlyShow) {
                $this->differencesDescription[] = 'different primary key definition';

                if ($this->getTableStructure()->isSchemaSQLite()) {
                    $this->differencesDescription[] = '(!) DROP/ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually';
                }
            } else {
                if (count($oldPrimaryKeyColumns)) {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->blueprint->dropPrimaryKey($oldPrimaryKey->getName());
                }

                if (!empty($this->getTableStructure()->primaryKey->columns) && $this->isPrimaryKeyComposite($newKeys)) {
                    $this->removeExcessivePrimaryKeyStatements($newKeys);

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->addPrimaryKey = $this->getTableStructure()->primaryKey;
                }
            }

            $different = true;
        }

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
                        . $this->displayValue($this->getTableStructure()->indexes[$name]->unique)
                        . ' <> MIG: unique ' . $this->displayValue($this->getOldStructure()->indexes[$name]->unique)
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

    /** @var StructureInterface */
    private $oldStructure;

    /**
     * Returns the table structure as applied in gathered migrations.
     * @return StructureInterface
     * @throws InvalidArgumentException
     */
    public function getOldStructure(): StructureInterface
    {
        if ($this->oldStructure === null) {
            $this->oldStructure = new Structure();
            $this->structureBuilder->setStructure($this->oldStructure);
            $this->structureBuilder->apply(array_reverse($this->appliedChanges));
        }

        return $this->oldStructure;
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

        return $append === '' && $autoIncrement === $column->isAutoIncrement() && $primaryKey === $column->isPrimaryKey();
    }

    /**
     * Returns values as strings.
     * @param mixed $value
     * @return string
     */
    private function displayValue($value): string
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
