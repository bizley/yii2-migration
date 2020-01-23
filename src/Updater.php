<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\Change;
use bizley\migration\table\Column;
use bizley\migration\table\Plan;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\Structure;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\console\controllers\MigrateController;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use function array_diff;
use function array_intersect;
use function array_key_exists;
use function array_merge;
use function array_reverse;
use function count;
use function file_exists;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function str_replace;
use function strcasecmp;
use function strpos;
use function trim;
use function usort;

class Updater extends Generator
{
    /** @var string Name of the table for keeping applied migration information */
    public $migrationTable = '{{%migration}}';

    /** @var string|array Directories storing the migration classes */
    public $migrationPath = '@app/migrations';

    /** @var bool Whether to only display changes instead of create updating migration */
    public $showOnly = false;

    /** @var array List of migration from the history that should be skipped during the update process */
    public $skipMigrations = [];

    /** @var string */
    private $currentTable;

    /**
     * Sets current table name and clears skipped migrations names.
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (empty($this->migrationPath)) {
            throw new InvalidConfigException('You must provide "migrationPath" value.');
        }

        if (is_array($this->migrationPath) === false) {
            $this->migrationPath = [$this->migrationPath];
        }

        $this->currentTable = $this->tableName;

        foreach ($this->skipMigrations as $index => $migration) {
            $this->skipMigrations[$index] = trim($migration, '\\');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function setDummyMigrationClass(): void
    {
        Yii::$classMap['yii\db\Migration'] = Yii::getAlias('@bizley/migration/dummy/Migration.php');
    }

    /**
     * Returns the migration history.
     * This is slightly modified Yii's MigrateController::getMigrationHistory() method.
     * Migrations are fetched from newest to oldest.
     * @return array the migration history
     */
    public function fetchHistory(): array
    {
        if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
            return [];
        }

        $rows
            = (new Query())
                ->select(['version', 'apply_time'])
                ->from($this->migrationTable)
                ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC])
                ->all($this->db);

        $history = [];

        foreach ($rows as $key => $row) {
            if ($row['version'] === MigrateController::BASE_MIGRATION) {
                continue;
            }

            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?$/is', $row['version'], $matches)) {
                $row['canonicalVersion'] = str_replace('_', '', $matches[1]);
            } else {
                $row['canonicalVersion'] = $row['version'];
            }

            $row['apply_time'] = (int)$row['apply_time'];

            $history[] = $row;
        }

        usort(
            $history,
            static function ($a, $b) {
                if ($a['apply_time'] === $b['apply_time']) {
                    if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                        return $compareResult;
                    }

                    return strcasecmp($b['version'], $a['version']);
                }

                return ($a['apply_time'] > $b['apply_time']) ? -1 : 1;
            }
        );

        return ArrayHelper::map($history, 'version', 'apply_time');
    }

    /** @var Change[] */
    private $appliedChanges = [];

    /**
     * @param array $changes
     * @return bool true if more data can be analysed or false if this must be last one
     * @throws InvalidConfigException
     */
    protected function gatherChanges(array $changes): bool
    {
        if (array_key_exists($this->currentTable, $changes) === false) {
            return true;
        }

        $data = array_reverse($changes[$this->currentTable]);

        /** @var $change Change */
        foreach ($data as $change) {
            if ($change->method === 'dropTable') {
                return false;
            }

            if ($change->method === 'renameTable') {
                $this->currentTable = $change->getValue();
                return $this->gatherChanges($changes);
            }

            $this->appliedChanges[] = $change;

            if ($change->method === 'createTable') {
                return false;
            }
        }

        return true;
    }

    /**
     * Extracts migration data structures.
     * @param string $migration
     * @return array
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    protected function extract(string $migration): array
    {
        if (strpos($migration, '\\') === false) {
            $fileFound = false;
            foreach ($this->migrationPath as $path) {
                $file = Yii::getAlias($path . DIRECTORY_SEPARATOR . $migration . '.php');
                if (file_exists($file)) {
                    $fileFound = true;
                    break;
                }
            }

            if (!$fileFound) {
                throw new ErrorException("File '{$migration}.php' can not be found! Check migration history table.");
            }

            require_once $file;
        }

        $subject = new $migration();
        $subject->db = $this->db;
        $subject->up();

        return $subject->changes;
    }

    /** @var Structure */
    protected $oldTable;

    /**
     * Returns the table structure as applied in gathered migrations.
     * @return Structure
     * @throws InvalidArgumentException
     */
    public function getOldTable(): Structure
    {
        if ($this->oldTable === null) {
            $this->oldTable
                = new Structure([
                    'schema' => get_class($this->db->schema),
                    'generalSchema' => $this->generalSchema,
                    'usePrefix' => $this->useTablePrefix,
                    'dbPrefix' => $this->db->tablePrefix,
                ]);

            $this->oldTable->applyChanges(array_reverse($this->appliedChanges));
        }

        return $this->oldTable;
    }

    /**
     * Returns values as strings.
     * @param mixed $value
     * @return string
     */
    public function displayValue($value): string
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

    /**
     * @param array $newKeys
     * @return bool
     * @throws InvalidConfigException
     */
    protected function isPrimaryKeyComposite(array $newKeys): bool
    {
        if (count($newKeys) === 1 && count($this->getTableStructure()->primaryKey->columns) === 1) {
            /** @var $column Column */
            foreach ($this->getPlan()->addColumn as $name => $column) {
                if ($name === $newKeys[0] && $column->isPrimaryKeyInfoAppended()) {
                    return false;
                }
            }

            foreach ($this->getPlan()->alterColumn as $name => $column) {
                if ($name === $newKeys[0] && $column->isPrimaryKeyInfoAppended()) {
                    return false;
                }
            }

            return true;
        }

        return true;
    }

    /**
     * @param array $newKeys
     * @throws InvalidConfigException
     */
    protected function removeExcessivePrimaryKeyStatements(array $newKeys): void
    {
        if (count($this->getTableStructure()->primaryKey->columns) > 1) {
            foreach ($newKeys as $key) {
                /* @var $column Column */
                foreach ($this->getPlan()->addColumn as $name => $column) {
                    if ($name === $key) {
                        $column->append = $column->removeAppendedPrimaryKeyInfo();
                    }
                }

                foreach ($this->getPlan()->alterColumn as $name => $column) {
                    if ($name === $key) {
                        $column->append = $column->removeAppendedPrimaryKeyInfo();
                    }
                }
            }
        }
    }

    /** @var Plan */
    private $modifications;

    /**
     * @return Plan
     */
    public function getPlan(): Plan
    {
        if ($this->modifications === null) {
            $this->modifications = new Plan();
        }

        return $this->modifications;
    }

    private function isAppendSame(string $append, Column $column): bool
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

        return $append === '' && $autoIncrement === $column->autoIncrement && $primaryKey === $column->isPrimaryKey;
    }

    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @return bool whether modification is required or not
     * @throws NotSupportedException
     * @throws InvalidConfigException
     */
    protected function compareStructures(): bool
    {
        if (empty($this->appliedChanges)) {
            return true;
        }

        $different = false;

        if ($this->showOnly) {
            echo "SHOWING DIFFERENCES:\n";
        }

        $previousColumn = null;
        foreach ($this->getTableStructure()->columns as $name => $column) {
            if (array_key_exists($name, $this->getOldTable()->columns) === false) {
                if ($this->showOnly) {
                    echo "   - missing column '$name'\n";
                } else {
                    if ($previousColumn) {
                        $column->after = $previousColumn;
                    } else {
                        $column->isFirst = true;
                    }
                    $this->getPlan()->addColumn[$name] = $column;
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
                    && $this->getTableStructure()->primaryKey->isComposite() === false
                    && $column->isColumnInPrimaryKey($this->getTableStructure()->primaryKey)
                ) {
                    $column->append = $column->prepareSchemaAppend(true, $column->autoIncrement);
                }

                $oldProperty = $this->getOldTable()->columns[$name]->$property;
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
                        && $this->isAppendSame($newProperty, $this->getOldTable()->columns[$name])
                    ) {
                        continue;
                    }

                    if ($this->showOnly) {
                        echo "   - different '$name' column property: $property (";
                        echo 'DB: ' . $this->displayValue($newProperty) . ' <> ';
                        echo 'MIG: ' . $this->displayValue($oldProperty) . ")\n";

                        if ($this->getTableStructure()->isSchemaSQLite()) {
                            echo "   (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually\n";
                        }
                    } elseif (!isset($this->getPlan()->alterColumn[$name])) {
                        if ($this->getTableStructure()->isSchemaSQLite()) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }

                        $this->getPlan()->alterColumn[$name] = $column;
                    }

                    $different = true;
                }
            }
        }

        foreach ($this->getOldTable()->columns as $name => $column) {
            if (array_key_exists($name, $this->getTableStructure()->columns) === false) {
                if ($this->showOnly) {
                    echo "   - excessive column '$name'\n";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) DROP COLUMN is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }

                    $this->getPlan()->dropColumn[] = $name;
                }

                $different = true;
            }
        }

        foreach ($this->getTableStructure()->foreignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $this->getOldTable()->foreignKeys) === false) {
                if ($this->showOnly) {
                    echo "   - missing foreign key '$name'\n";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->addForeignKey[$name] = $foreignKey;
                }

                $different = true;

                continue;
            }

            $tableFKColumns
                = !empty($this->getTableStructure()->foreignKeys[$name]->columns)
                    ? $this->getTableStructure()->foreignKeys[$name]->columns
                    : [];
            $oldTableFKColumns
                = !empty($this->getOldTable()->foreignKeys[$name]->columns)
                    ? $this->getOldTable()->foreignKeys[$name]->columns
                    : [];

            if (
                count(
                    array_merge(
                        array_diff($tableFKColumns, array_intersect($tableFKColumns, $oldTableFKColumns)),
                        array_diff($oldTableFKColumns, array_intersect($tableFKColumns, $oldTableFKColumns))
                    )
                )
            ) {
                if ($this->showOnly) {
                    echo "   - different foreign key '$name' columns (";
                    echo 'DB: (' . implode(', ', $tableFKColumns) . ') <> ';
                    echo 'MIG: (' . implode(', ', $oldTableFKColumns) . "))\n";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->dropForeignKey[] = $name;
                    $this->getPlan()->addForeignKey[$name] = $foreignKey;
                }

                $different = true;

                continue;
            }

            $tableFKRefColumns
                = !empty($this->getTableStructure()->foreignKeys[$name]->refColumns)
                    ? $this->getTableStructure()->foreignKeys[$name]->refColumns
                    : [];
            $oldTableFKRefColumns
                = !empty($this->getOldTable()->foreignKeys[$name]->refColumns)
                    ? $this->getOldTable()->foreignKeys[$name]->refColumns
                    : [];

            if (
                count(
                    array_merge(
                        array_diff($tableFKRefColumns, array_intersect($tableFKRefColumns, $oldTableFKRefColumns)),
                        array_diff($oldTableFKRefColumns, array_intersect($tableFKRefColumns, $oldTableFKRefColumns))
                    )
                )
            ) {
                if ($this->showOnly) {
                    echo "   - different foreign key '$name' referral columns (";
                    echo 'DB: (' . implode(', ', $tableFKRefColumns) . ') <> ';
                    echo 'MIG: (' . implode(', ', $oldTableFKRefColumns) . "))\n";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->dropForeignKey[] = $name;
                    $this->getPlan()->addForeignKey[$name] = $foreignKey;
                }

                $different = true;
            }
        }

        foreach ($this->getOldTable()->foreignKeys as $name => $foreignKey) {
            if (array_key_exists($name, $this->getTableStructure()->foreignKeys) === false) {
                if ($this->showOnly) {
                    echo "   - excessive foreign key '$name'\n";

                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        echo "   (!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->dropForeignKey[] = $name;
                }

                $different = true;
            }
        }

        $tablePKColumns = !empty($this->getTableStructure()->primaryKey->columns)
            ? $this->getTableStructure()->primaryKey->columns
            : [];
        $oldTablePKColumns
            = !empty($this->getOldTable()->primaryKey->columns) ? $this->getOldTable()->primaryKey->columns : [];

        $newKeys = array_merge(
            array_diff($tablePKColumns, array_intersect($tablePKColumns, $oldTablePKColumns)),
            array_diff($oldTablePKColumns, array_intersect($tablePKColumns, $oldTablePKColumns))
        );

        if (count($newKeys)) {
            if ($this->showOnly) {
                echo "   - different primary key definition\n";

                if ($this->getTableStructure()->isSchemaSQLite()) {
                    echo "   (!) DROP/ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually\n";
                }
            } else {
                if (count($this->getOldTable()->primaryKey->columns)) {
                    if ($this->getTableStructure()->isSchemaSQLite()) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->getPlan()->dropPrimaryKey
                        = $this->getOldTable()->primaryKey->name ?: PrimaryKey::GENERIC_PRIMARY_KEY;
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
            if (array_key_exists($name, $this->getOldTable()->indexes) === false) {
                if ($this->showOnly) {
                    echo "   - missing index '$name'\n";
                } else {
                    $this->getPlan()->createIndex[$name] = $index;
                }

                $different = true;

                continue;
            }

            if ($this->getOldTable()->indexes[$name]->unique !== $this->getTableStructure()->indexes[$name]->unique) {
                if ($this->showOnly) {
                    echo "   - different index '$name' definition (DB: unique "
                        . $this->displayValue($this->getTableStructure()->indexes[$name]->unique)
                        . ' <> MIG: unique ' . $this->displayValue($this->getOldTable()->indexes[$name]->unique)
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
                = !empty($this->getOldTable()->indexes[$name]->columns)
                    ? $this->getOldTable()->indexes[$name]->columns
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

        foreach ($this->getOldTable()->indexes as $name => $index) {
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
     * @return bool
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function isUpdateRequired(): bool
    {
        $history = $this->fetchHistory();

        if (!empty($history)) {
            $this->setDummyMigrationClass();

            foreach ($history as $migration => $time) {
                $migration = trim($migration, '\\');
                if (in_array($migration, $this->skipMigrations, true)) {
                    continue;
                }

                if (!$this->gatherChanges($this->extract($migration))) {
                    break;
                }
            }

            return $this->compareStructures();
        }

        return true;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function generateMigration(): string
    {
        if (empty($this->modifications)) {
            return parent::generateMigration();
        }

        return $this->view->renderFile(
            Yii::getAlias($this->templateFileUpdate),
            [
                'className' => $this->className,
                'table' => $this->getTableStructure(),
                'plan' => $this->getPlan(),
                'namespace' => $this->getNormalizedNamespace()
            ]
        );
    }
}
