<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\TableChange;
use bizley\migration\table\TableColumn;
use bizley\migration\table\TablePlan;
use bizley\migration\table\TablePrimaryKey;
use bizley\migration\table\TableStructure;
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

/**
 * Class Updater
 * @package bizley\migration
 *
 * @property-read TableStructure $oldTable
 * @property TablePlan $plan
 */
class Updater extends Generator
{
    /**
     * @var string Name of the table for keeping applied migration information.
     */
    public $migrationTable = '{{%migration}}';

    /**
     * @var string|array Directory storing the migration classes. This can be either a path alias or a directory.
     * Since 3.5.0 this can be array of directories.
     */
    public $migrationPath = '@app/migrations';

    /**
     * @var bool Whether to only display changes instead of create updating migration.
     */
    public $showOnly = false;

    /**
     * @var array List of migration from the history that should be skipped during the update process.
     * Here you can place migrations containing actions that can not be covered by extractor.
     * @since 2.1.1
     */
    public $skipMigrations = [];

    private $_currentTable;

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

        if (!is_array($this->migrationPath)) {
            $this->migrationPath = [$this->migrationPath];
        }

        $this->_currentTable = $this->tableName;

        foreach ($this->skipMigrations as $index => $migration) {
            $this->skipMigrations[$index] = trim($migration, '\\');
        }
    }

    /**
     * Sets dummy Migration class.
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

        $rows = (new Query())
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

        usort($history, static function ($a, $b) {
            if ($a['apply_time'] === $b['apply_time']) {
                if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                    return $compareResult;
                }

                return strcasecmp($b['version'], $a['version']);
            }

            return ($a['apply_time'] > $b['apply_time']) ? -1 : 1;
        });

        return ArrayHelper::map($history, 'version', 'apply_time');
    }

    private $_appliedChanges = [];

    /**
     * Gathers applied changes.
     * @param array $changes
     * @return bool true if more data can be analysed or false if this must be last one
     * @since 2.3.0
     */
    protected function gatherChanges(array $changes): bool
    {
        if (!isset($changes[$this->_currentTable])) {
            return true;
        }

        $data = array_reverse($changes[$this->_currentTable]);

        /* @var $tableChange TableChange */
        foreach ($data as $tableChange) {
            if ($tableChange->method === 'dropTable') {
                return false;
            }

            if ($tableChange->method === 'renameTable') {
                $this->_currentTable = $tableChange->value;
                return $this->gatherChanges($changes);
            }

            $this->_appliedChanges[] = $tableChange;

            if ($tableChange->method === 'createTable') {
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

    protected $_oldTable;

    /**
     * Returns the table structure as applied in gathered migrations.
     * @return TableStructure
     * @since 2.3.0
     * @throws InvalidArgumentException
     */
    public function getOldTable(): TableStructure
    {
        if ($this->_oldTable === null) {
            $this->_oldTable = new TableStructure([
                'schema' => get_class($this->db->schema),
                'generalSchema' => $this->generalSchema,
                'usePrefix' => $this->useTablePrefix,
                'dbPrefix' => $this->db->tablePrefix,
            ]);

            $this->_oldTable->applyChanges(array_reverse($this->_appliedChanges));
        }

        return $this->_oldTable;
    }

    /**
     * Returns values as a string.
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
     * Confirms adding composite primary key and removes excessive PK statements.
     * @param array $newKeys
     * @return bool
     * @since 2.1.2
     */
    protected function confirmCompositePrimaryKey(array $newKeys): bool
    {
        if (count($this->table->primaryKey->columns) === 1 && count($newKeys) === 1) {
            /* @var $column TableColumn */
            foreach ($this->plan->addColumn as $name => $column) {
                if ($name === $newKeys[0] && $column->isColumnAppendPK()) {
                    return false;
                }
            }

            foreach ($this->plan->alterColumn as $name => $column) {
                if ($name === $newKeys[0] && $column->isColumnAppendPK()) {
                    return false;
                }
            }

            return true;
        }

        if (count($this->table->primaryKey->columns) > 1) {
            foreach ($newKeys as $key) {
                /* @var $column TableColumn */
                foreach ($this->plan->addColumn as $name => $column) {
                    if ($name === $key) {
                        $column->append = $column->removePKAppend();
                    }
                }

                foreach ($this->plan->alterColumn as $name => $column) {
                    if ($name === $key) {
                        $column->append = $column->removePKAppend();
                    }
                }
            }
        }

        return true;
    }

    private $_modifications;

    /**
     * @return TablePlan
     */
    public function getPlan(): TablePlan
    {
        if ($this->_modifications === null) {
            $this->_modifications = new TablePlan();
        }

        return $this->_modifications;
    }

    /**
     * Compares migration structure and database structure and gather required modifications.
     * @return bool whether modification is required or not
     * @throws NotSupportedException
     */
    protected function compareStructures(): bool
    {
        if (empty($this->_appliedChanges)) {
            return true;
        }

        $different = false;

        if ($this->showOnly) {
            echo "SHOWING DIFFERENCES:\n";
        }

        $previousColumn = null;
        foreach ($this->table->columns as $name => $column) {
            if (!isset($this->oldTable->columns[$name])) {
                if ($this->showOnly) {
                    echo "   - missing column '$name'\n";
                } else {
                    $column->after = $previousColumn;
                    $this->plan->addColumn[$name] = $column;
                }

                $different = true;
                $previousColumn = $name;

                continue;
            }

            $previousColumn = $name;

            foreach ([
                'type',
                'isNotNull',
                'length',
                'isUnique',
                'isUnsigned',
                'default',
                'append',
                'comment'
             ] as $property) {
                if ($this->generalSchema && $property === 'length') {
                    continue;
                }

                if (!$this->generalSchema
                    && $property === 'append'
                    && $column->append === null
                    && !$this->table->primaryKey->isComposite()
                    && $column->isColumnInPK($this->table->primaryKey)
                ) {
                    $column->append = $column->prepareSchemaAppend(true, $column->autoIncrement);
                }

                if ($this->oldTable->columns[$name]->$property !== $column->$property) {
                    if ($this->showOnly) {
                        echo "   - different '$name' column property: $property (";
                        echo 'DB: ' . $this->displayValue($column->$property) . ' <> ';
                        echo 'MIG: ' . $this->displayValue($this->oldTable->columns[$name]->$property) . ")\n";

                        if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                            echo "   (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually\n";
                        }
                    } elseif (!isset($this->plan->alterColumn[$name])) {
                        if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                            throw new NotSupportedException('ALTER COLUMN is not supported by SQLite.');
                        }

                        $this->plan->alterColumn[$name] = $column;
                    }

                    $different = true;
                }
            }
        }

        foreach ($this->oldTable->columns as $name => $column) {
            if (!isset($this->table->columns[$name])) {
                if ($this->showOnly) {
                    echo "   - excessive column '$name'\n";

                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        echo "   (!) DROP COLUMN is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('DROP COLUMN is not supported by SQLite.');
                    }

                    $this->plan->dropColumn[] = $name;
                }

                $different = true;
            }
        }

        foreach ($this->table->foreignKeys as $name => $foreignKey) {
            if (!isset($this->oldTable->foreignKeys[$name])) {
                if ($this->showOnly) {
                    echo "   - missing foreign key '$name'\n";

                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        echo "   (!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->plan->addForeignKey[$name] = $foreignKey;
                }

                $different = true;

                continue;
            }

            $tableFKColumns = !empty($this->table->foreignKeys[$name]->columns)
                ? $this->table->foreignKeys[$name]->columns
                : [];
            $oldTableFKColumns = !empty($this->oldTable->foreignKeys[$name]->columns)
                ? $this->oldTable->foreignKeys[$name]->columns
                : [];

            if (count(
                array_merge(
                    array_diff($tableFKColumns, array_intersect($tableFKColumns, $oldTableFKColumns)),
                    array_diff($oldTableFKColumns, array_intersect($tableFKColumns, $oldTableFKColumns))
                )
            )) {
                if ($this->showOnly) {
                    echo "   - different foreign key '$name' columns (";
                    echo 'DB: (' . implode(', ', $tableFKColumns) . ') <> ';
                    echo 'MIG: (' . implode(', ', $oldTableFKColumns) . "))\n";

                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        echo "   (!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->plan->dropForeignKey[] = $name;
                    $this->plan->addForeignKey[$name] = $foreignKey;
                }

                $different = true;

                continue;
            }

            $tableFKRefColumns = !empty($this->table->foreignKeys[$name]->refColumns)
                ? $this->table->foreignKeys[$name]->refColumns
                : [];
            $oldTableFKRefColumns = !empty($this->oldTable->foreignKeys[$name]->refColumns)
                ? $this->oldTable->foreignKeys[$name]->refColumns
                : [];

            if (count(
                array_merge(
                    array_diff($tableFKRefColumns, array_intersect($tableFKRefColumns, $oldTableFKRefColumns)),
                    array_diff($oldTableFKRefColumns, array_intersect($tableFKRefColumns, $oldTableFKRefColumns))
                )
            )) {
                if ($this->showOnly) {
                    echo "   - different foreign key '$name' referral columns (";
                    echo 'DB: (' . implode(', ', $tableFKRefColumns) . ') <> ';
                    echo 'MIG: (' . implode(', ', $oldTableFKRefColumns) . "))\n";

                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        echo "   (!) DROP/ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('DROP/ADD FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->plan->dropForeignKey[] = $name;
                    $this->plan->addForeignKey[$name] = $foreignKey;
                }

                $different = true;
            }
        }

        foreach ($this->oldTable->foreignKeys as $name => $foreignKey) {
            if (!isset($this->table->foreignKeys[$name])) {
                if ($this->showOnly) {
                    echo "   - excessive foreign key '$name'\n";

                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        echo "   (!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually\n";
                    }
                } else {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('DROP FOREIGN KEY is not supported by SQLite.');
                    }

                    $this->plan->dropForeignKey[] = $name;
                }

                $different = true;
            }
        }

        $tablePKColumns = !empty($this->table->primaryKey->columns) ? $this->table->primaryKey->columns : [];
        $oldTablePKColumns = !empty($this->oldTable->primaryKey->columns) ? $this->oldTable->primaryKey->columns : [];

        $newKeys = array_merge(
            array_diff($tablePKColumns, array_intersect($tablePKColumns, $oldTablePKColumns)),
            array_diff($oldTablePKColumns, array_intersect($tablePKColumns, $oldTablePKColumns))
        );

        if (count($newKeys)) {
            if ($this->showOnly) {
                echo "   - different primary key definition\n";

                if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                    echo "   (!) DROP/ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually\n";
                }
            } else {
                if (!empty($this->oldTable->primaryKey->columns)) {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('DROP PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->plan->dropPrimaryKey = $this->oldTable->primaryKey->name
                        ?: TablePrimaryKey::GENERIC_PRIMARY_KEY;
                }

                if (!empty($this->table->primaryKey->columns) && $this->confirmCompositePrimaryKey($newKeys)) {
                    if ($this->table->getSchema() === TableStructure::SCHEMA_SQLITE) {
                        throw new NotSupportedException('ADD PRIMARY KEY is not supported by SQLite.');
                    }

                    $this->plan->addPrimaryKey = $this->table->primaryKey;
                }
            }

            $different = true;
        }

        foreach ($this->table->indexes as $name => $index) {
            if (!isset($this->oldTable->indexes[$name])) {
                if ($this->showOnly) {
                    echo "   - missing index '$name'\n";
                } else {
                    $this->plan->createIndex[$name] = $index;
                }

                $different = true;

                continue;
            }

            if ($this->oldTable->indexes[$name]->unique !== $this->table->indexes[$name]->unique) {
                if ($this->showOnly) {
                    echo "   - different index '$name' definition (";
                    echo 'DB: unique ' . $this->displayValue($this->table->indexes[$name]->unique) . ' <> ';
                    echo 'MIG: unique ' . $this->displayValue($this->oldTable->indexes[$name]->unique) . ")\n";
                } else {
                    $this->plan->dropIndex[] = $name;
                    $this->plan->createIndex[$name] = $index;
                }

                $different = true;

                continue;
            }

            $tableIndexColumns = !empty($this->table->indexes[$name]->columns)
                ? $this->table->indexes[$name]->columns
                : [];
            $oldTableIndexColumns = !empty($this->oldTable->indexes[$name]->columns)
                ? $this->oldTable->indexes[$name]->columns
                : [];

            if (count(
                array_merge(
                    array_diff($tableIndexColumns, array_intersect($tableIndexColumns, $oldTableIndexColumns)),
                    array_diff($oldTableIndexColumns, array_intersect($tableIndexColumns, $oldTableIndexColumns))
                )
            )) {
                if ($this->showOnly) {
                    echo "   - different index '$name' columns (";
                    echo 'DB: (' . implode(', ', $tableIndexColumns) . ') <> ';
                    echo 'MIG: (' . implode(', ', $oldTableIndexColumns) . "))\n";
                } else {
                    $this->plan->dropIndex[] = $name;
                    $this->plan->createIndex[$name] = $index;
                }

                $different = true;
            }
        }

        foreach ($this->oldTable->indexes as $name => $index) {
            if (!isset($this->table->indexes[$name])) {
                if ($this->showOnly) {
                    echo "   - excessive index '$name'\n";
                } else {
                    $this->plan->dropIndex[] = $name;
                }

                $different = true;
            }
        }

        return $different;
    }

    /**
     * Checks if new updating migration is required.
     * @return bool
     * @throws ErrorException
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
     * Generates migration content or echoes exception message.
     * @return string
     */
    public function generateMigration(): string
    {
        if (empty($this->_modifications)) {
            return parent::generateMigration();
        }

        return $this->view->renderFile(Yii::getAlias($this->templateFileUpdate), [
            'className' => $this->className,
            'table' => $this->table,
            'plan' => $this->plan,
            'namespace' => $this->normalizedNamespace
        ]);
    }
}
