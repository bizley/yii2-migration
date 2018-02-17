<?php

namespace bizley\migration;

use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\console\controllers\MigrateController;
use yii\db\Expression;
use yii\db\Query;
use yii\db\Schema;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Update migration file generator.
 *
 * @author Paweł Bizley Brzozowski
 * @version 2.2.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class Updater extends Generator
{
    /**
     * @var string Name of the table for keeping applied migration information.
     */
    public $migrationTable = '{{%migration}}';

    /**
     * @var string Directory storing the migration classes. This can be either a path alias or a directory.
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
     * Sets subject table name and clears skipped migrations names.
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->_currentTable = $this->tableName;
        foreach ($this->skipMigrations as $index => $migration) {
            $this->skipMigrations[$index] = trim($migration, '\\');
        }
    }

    private $_originalMigrationClass;

    /**
     * Sets dummy Migration class.
     * @throws InvalidParamException
     */
    protected function setDummyMigrationClass()
    {
        $this->_originalMigrationClass = Yii::$classMap['yii\db\Migration'];
        Yii::$classMap['yii\db\Migration'] = Yii::getAlias('@vendor/bizley/migration/src/dummy/Migration.php');
    }

    /**
     * Restores original Migration class.
     */
    protected function restoreMigrationClass()
    {
        Yii::$classMap['yii\db\Migration'] = $this->_originalMigrationClass;
    }

    /**
     * Returns the migration history.
     * This is slightly modified MigrateController::getMigrationHistory() method.
     * Migrations are fetched from newest to oldest.
     * @return array the migration history
     */
    public function fetchHistory()
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
                $time = str_replace('_', '', $matches[1]);
                $row['canonicalVersion'] = $time;
            } else {
                $row['canonicalVersion'] = $row['version'];
            }
            $row['apply_time'] = (int)$row['apply_time'];
            $history[] = $row;
        }

        usort($history, function ($a, $b) {
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

    private $_oldSchema = [];

    /**
     * Analyses gathered changes.
     * @param array $changes
     * @return bool true if more data can be analysed or false if this must be last one
     */
    protected function analyseChanges($changes)
    {
        if (!isset($changes[$this->_currentTable])) {
            return true;
        }
        $data = array_reverse($changes[$this->_currentTable]);
        foreach ($data as $method => $details) {
            if ($method === 'dropTable') {
                return false;
            }
            if ($method === 'renameTable') {
                $this->_currentTable = $details;
                return $this->analyseChanges($changes);
            }
            $this->_oldSchema[] = $method;
            if ($method === 'createTable') {
                return false;
            }
        }
        return true;
    }

    /**
     * Extracts migration data structures.
     * @param string $migration
     * @return array
     * @throws InvalidParamException
     * @throws ErrorException
     */
    protected function extract($migration)
    {
        if (strpos($migration, '\\') === false) {
            $file = Yii::getAlias($this->migrationPath . DIRECTORY_SEPARATOR . $migration . '.php');
            if (!file_exists($file)) {
                throw new ErrorException("File '{$file}' can not be found! Check migration history table.");
            }
            require_once $file;
        }

        $subject = new $migration;
        $subject->db = $this->db;
        $subject->up();

        return $subject->changes;
    }

    private $_oldStructure = [];

    /**
     * Formats the gathered migrations structure.
     */
    protected function formatStructure()
    {
        $this->_oldStructure['columns'] = [];
        $this->_oldStructure['fks'] = [];
        $this->_oldStructure['pk'] = [];
        $this->_oldStructure['uidxs'] = [];

        $changes = array_reverse($this->_oldSchema);
        foreach ($changes as $change) {
            switch (key($change)) {
                case 'createTable':
                case 'addColumn':
                    foreach (current($change) as $column => $properties) {
                        $this->_oldStructure['columns'][$column] = $properties;
                        if (!empty($this->_oldStructure['columns'][$column]['append']) && $this->findPrimaryKeyString($this->_oldStructure['columns'][$column]['append'])) {
                            $this->_oldStructure['pk'][] = $column;
                        }
                    }
                    break;
                case 'dropColumn':
                    if (isset($this->_oldStructure['columns'][current($change)])) {
                        unset($this->_oldStructure['columns'][current($change)]);
                    }
                    break;
                case 'renameColumn':
                    if (isset($this->_oldStructure['columns'][key(current($change))])) {
                        $this->_oldStructure['columns'][current(current($change))] = $this->_oldStructure['columns'][key(current($change))];
                        unset($this->_oldStructure['columns'][key(current($change))]);
                    }
                    break;
                case 'alterColumn':
                    if (isset($this->_oldStructure['columns'][key(current($change))])) {
                        $this->_oldStructure['columns'][key(current($change))] = current(current($change));
                    }
                    break;
                case 'addPrimaryKey':
                    $pk = current($change);
                    $this->_oldStructure['pk'] = $pk;
                    foreach ($pk as $key) {
                        if (isset($this->_oldStructure['columns'][$key])) {
                            if (empty($this->_oldStructure['columns'][$key]['append'])) {
                                $this->_oldStructure['columns'][$key]['append'] = $this->prepareSchemaAppend(true, false);
                            } elseif (!$this->findPrimaryKeyString($this->_oldStructure['columns'][$key]['append'])) {
                                $this->_oldStructure['columns'][$key]['append'] .= ' ' . $this->prepareSchemaAppend(true, false);
                            }
                        }
                    }
                    break;
                case 'dropPrimaryKey':
                    if (!empty($this->_oldStructure['pk'])) {
                        foreach ($this->_oldStructure['pk'] as $key) {
                            if (isset($this->_oldStructure['columns'][$key]) && !empty($this->_oldStructure['columns'][$key]['append'])) {
                                $append = $this->removePrimaryKeyString($this->_oldStructure['columns'][$key]['append']);
                                if ($append) {
                                    $this->_oldStructure['columns'][$key]['append'] = !is_string($append) || $append === ' ' ? null : $append;
                                }
                            }
                        }
                    }
                    $this->_oldStructure['pk'] = [];
                    break;
                case 'addForeignKey':
                    $this->_oldStructure['fks'][current($change)[0]] = [current($change)[1], current($change)[2], current($change)[3], current($change)[4], current($change)[5]];
                    break;
                case 'dropForeignKey':
                    if (isset($this->_oldStructure['fks'][current($change)])) {
                        unset($this->_oldStructure['fks'][current($change)]);
                    }
                    break;
                case 'createIndex':
                    $this->_oldStructure['uidxs'][key(current($change))] = current(current($change));
                    break;
                case 'dropIndex':
                    if (isset($this->_oldStructure['uidxs'][current($change)])) {
                        unset($this->_oldStructure['uidxs'][current($change)]);
                    }
                    break;
                case 'addCommentOnColumn':
                    if (isset($this->_oldStructure['columns'][key(current($change))])) {
                        $this->_oldStructure['columns'][key(current($change))]['comment'] = current(current($change));
                    }
                    break;
                case 'dropCommentFromColumn':
                    if (isset($this->_oldStructure['columns'][current($change)])) {
                        $this->_oldStructure['columns'][current($change)]['comment'] = null;
                    }
            }
        }
    }

    /**
     * Returns values as a string.
     * @param mixed $value
     * @return string
     */
    public function displayValue($value)
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
        return '"' . $value . '"';
    }

    /**
     * Confirms adding composite primary key and removes excessive PK statements.
     * @param array $newKeys
     * @return bool
     * @since 2.1.2
     */
    protected function confirmCompositePrimaryKey($newKeys)
    {
        $primaryKeyColumns = array_key_exists('columnNames', $this->structure['pk']) ? $this->structure['pk']['columnNames'] : [];
        if (count($primaryKeyColumns) === 1 && count($newKeys) === 1) {
            if (isset($this->_modifications['addColumn'])) {
                foreach ($this->_modifications['addColumn'] as $column => $data) {
                    if ($column === $newKeys[0] && !empty($data['append']) && $this->findPrimaryKeyString($data['append'])) {
                        return false;
                    }
                }
            }
            if (isset($this->_modifications['alterColumn'])) {
                foreach ($this->_modifications['alterColumn'] as $column => $data) {
                    if ($column === $newKeys[0] && !empty($data['append']) && $this->findPrimaryKeyString($data['append'])) {
                        return false;
                    }
                }
            }
            return true;
        }
        if (count($primaryKeyColumns) > 1) {
            foreach ($newKeys as $key) {
                if (isset($this->_modifications['addColumn'])) {
                    foreach ($this->_modifications['addColumn'] as $column => $data) {
                        if ($column === $key && !empty($data['append'])) {
                            $append = $this->removePrimaryKeyString($data['append']);
                            if ($append) {
                                $this->_modifications['addColumn'][$column]['append'] = !is_string($append) || $append === ' ' ? null : $append;
                            }
                        }
                    }
                }
                if (isset($this->_modifications['alterColumn'])) {
                    foreach ($this->_modifications['alterColumn'] as $column => $data) {
                        if ($column === $key && !empty($data['append'])) {
                            $append = $this->removePrimaryKeyString($data['append']);
                            if ($append) {
                                $this->_modifications['alterColumn'][$column]['append'] = !is_string($append) || $append === ' ' ? null : $append;
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    private $_modifications = [];

    /**
     * Compares migration structure and database structure and gather required modifications.
     * @return bool whether modification is required or not
     */
    protected function compareStructures()
    {
        if (empty($this->_oldSchema)) {
            return true;
        }
        $this->formatStructure();
        $different = false;
        if ($this->showOnly) {
            echo "SHOWING DIFFERENCES:\n";
        }
        foreach ($this->structure['columns'] as $column => $data) {
            if (!isset($this->_oldStructure['columns'][$column])) {
                if ($this->showOnly) {
                    echo "   - missing column '$column'\n";
                }
                $this->_modifications['addColumn'][$column] = $data;
                $different = true;
                continue;
            }
            foreach ($data as $property => $value) {
                if ($value !== null && !isset($this->_oldStructure['columns'][$column][$property])) {
                    if ($this->showOnly) {
                        echo "   - missing '$column' column property: $property (";
                        echo 'DB: ' . $this->displayValue($value) . ")\n";
                    }
                    $this->_modifications['alterColumn'][$column] = $data;
                    $different = true;
                    break;
                }
                if ($this->_oldStructure['columns'][$column][$property] != $value) {
                    if (!$this->generalSchema || $property !== 'length') {
                        if ($this->showOnly) {
                            echo "   - different '$column' column property: $property (";
                            echo 'DB: ' . $this->displayValue($value) . ' <> ';
                            echo 'MIG: ' . $this->displayValue($this->_oldStructure['columns'][$column][$property]) . ")\n";
                        }
                        $this->_modifications['alterColumn'][$column] = $data;
                        $different = true;
                        break;
                    }
                }
            }
        }
        foreach ($this->_oldStructure['columns'] as $column => $data) {
            if (!isset($this->structure['columns'][$column])) {
                if ($this->showOnly) {
                    echo "   - excessive column '$column'\n";
                }
                $this->_modifications['dropColumn'][] = $column;
                $different = true;
            }
        }

        foreach ($this->structure['fks'] as $fk => $data) {
            if (!isset($this->_oldStructure['fks'][$fk])) {
                if ($this->showOnly) {
                    echo "   - missing foreign key '$fk'\n";
                }
                $this->_modifications['addForeignKey'][$fk] = $data;
                $different = true;
                continue;
            }
        }
        foreach ($this->_oldStructure['fks'] as $fk => $data) {
            if (!isset($this->structure['fks'][$fk])) {
                if ($this->showOnly) {
                    echo "   - excessive foreign key '$fk'\n";
                }
                $this->_modifications['dropForeignKey'][] = $fk;
                $different = true;
            }
        }

        $newKeys = array_diff($this->structure['pk'], $this->_oldStructure['pk']);
        if (count($newKeys)) {
            if ($this->showOnly) {
                echo "   - different primary key definition\n";
            }
            if (!empty($this->_oldStructure['pk'])) {
                $this->_modifications['dropPrimaryKey'] = true;
            }
            if (!empty($this->structure['pk']) && $this->confirmCompositePrimaryKey($newKeys)) {
                $this->_modifications['addPrimaryKey'] = $this->structure['pk'];
            }
            $different = true;
        }

        foreach ($this->structure['uidxs'] as $uidx => $data) {
            if (!isset($this->_oldStructure['uidxs'][$uidx])) {
                if ($this->showOnly) {
                    echo "   - missing unique index '$uidx'\n";
                }
                $this->_modifications['createIndex'][$uidx] = $data;
                $different = true;
                continue;
            }
        }
        foreach ($this->_oldStructure['uidxs'] as $uidx => $data) {
            if (!isset($this->structure['uidxs'][$uidx])) {
                if ($this->showOnly) {
                    echo "   - excessive unique index '$uidx'\n";
                }
                $this->_modifications['dropIndex'][] = $uidx;
                $different = true;
            }
        }

        return $different;
    }

    /**
     * Checks if new updating migration is required.
     * @return bool
     * @throws InvalidParamException
     * @throws ErrorException
     */
    public function isUpdateRequired()
    {
        $history = $this->fetchHistory();
        if (!empty($history)) {
            $this->setDummyMigrationClass();
            foreach ($history as $migration => $time) {
                $migration = trim($migration, '\\');
                if (in_array($migration, $this->skipMigrations, true)) {
                    continue;
                }
                if (!$this->analyseChanges($this->extract($migration))) {
                    break;
                }
            }
            $this->restoreMigrationClass();
            return $this->compareStructures();
        }
        return true;
    }

    /**
     * Returns column definition based on data array.
     * @param array $column
     * @return string
     */
    public function renderColumnStructure($column)
    {
        $definition = '$this';
        $checkNotNull = true;
        $checkUnsigned = true;
        $schema = $this->db->schema;
        $size = $this->renderSizeStructure($column);
        if ($this->generalSchema) {
            $size = '';
        }
        switch ($column['type']) {
            case Schema::TYPE_UPK:
                if ($this->generalSchema) {
                    $checkUnsigned = false;
                    $definition .= '->unsigned()';
                }
                // no break
            case Schema::TYPE_PK:
                if ($this->generalSchema) {
                    if ($schema::className() !== 'yii\db\mssql\Schema') {
                        $checkNotNull = false;
                    }
                }
                $definition .= '->primaryKey(' . $size . ')';
                break;
            case Schema::TYPE_UBIGPK:
                if ($this->generalSchema) {
                    $checkUnsigned = false;
                    $definition .= '->unsigned()';
                }
                // no break
            case Schema::TYPE_BIGPK:
                if ($this->generalSchema) {
                    if ($schema::className() !== 'yii\db\mssql\Schema') {
                        $checkNotNull = false;
                    }
                }
                $definition .= '->bigPrimaryKey(' . $size . ')';
                break;
            case Schema::TYPE_CHAR:
                $definition .= '->char(' . $size . ')';
                break;
            case Schema::TYPE_STRING:
                $definition .= '->string(' . $size . ')';
                break;
            case Schema::TYPE_TEXT:
                $definition .= '->text(' . $size . ')';
                break;
            case Schema::TYPE_SMALLINT:
                $definition .= '->smallInteger(' . $size . ')';
                break;
            case Schema::TYPE_INTEGER:
                if ($this->generalSchema && array_key_exists('append', $column)) {
                    $append = $this->removePrimaryKeyString($column['append']);
                    if ($append) {
                        $definition .= '->primaryKey()';
                        $column['append'] = !is_string($append) || $append === ' ' ? null : $append;
                    }
                } else {
                    $definition .= '->integer(' . $size . ')';
                }
                break;
            case Schema::TYPE_BIGINT:
                if ($this->generalSchema && array_key_exists('append', $column)) {
                    $append = $this->removePrimaryKeyString($column['append']);
                    if ($append) {
                        $definition .= '->bigPrimaryKey()';
                        $column['append'] = !is_string($append) || $append === ' ' ? null : $append;
                    }
                } else {
                    $definition .= '->bigInteger(' . $size . ')';
                }
                break;
            case Schema::TYPE_FLOAT:
                $definition .= '->float(' . $size . ')';
                break;
            case Schema::TYPE_DOUBLE:
                $definition .= '->double(' . $size . ')';
                break;
            case Schema::TYPE_DECIMAL:
                $definition .= '->decimal(' . $size . ')';
                break;
            case Schema::TYPE_DATETIME:
                $definition .= '->dateTime(' . $size . ')';
                break;
            case Schema::TYPE_TIMESTAMP:
                $definition .= '->timestamp(' . $size . ')';
                break;
            case Schema::TYPE_TIME:
                $definition .= '->time(' . $size . ')';
                break;
            case Schema::TYPE_DATE:
                $definition .= '->date()';
                break;
            case Schema::TYPE_BINARY:
                $definition .= '->binary(' . $size . ')';
                break;
            case Schema::TYPE_BOOLEAN:
                $definition .= '->boolean()';
                break;
            case Schema::TYPE_MONEY:
                $definition .= '->money(' . $size . ')';
        }
        if ($checkUnsigned && array_key_exists('isUnsigned', $column) && $column['isUnsigned']) {
            $definition .= '->unsigned()';
        }
        if ($checkNotNull && array_key_exists('isNotNull', $column) && $column['isNotNull']) {
            $definition .= '->notNull()';
        }
        if (array_key_exists('default', $column) && $column['default'] !== null) {
            if ($column['default'] instanceof Expression) {
                $definition .= '->defaultExpression(\'' . $column['default']->expression . '\')';
            } else {
                $definition .= '->defaultValue(\'' . str_replace("'", "\'", $column['default']) . '\')';
            }
        }
        if (array_key_exists('comment', $column) && $column['comment']) {
            $definition .= '->comment(\'' . str_replace("'", "\'", $column['comment']) . '\')';
        }
        if (array_key_exists('append', $column) && $column['append']) {
            $definition .= '->append(\'' . $column['append'] . '\')';
        }

        return $definition;
    }

    /**
     * Checks for primary key string based on column properties and used schema.
     * @param string $append
     * @return bool
     * @since 2.1.2
     */
    public function findPrimaryKeyString($append)
    {
        $schema = $this->db->schema;
        if ($schema::className() === 'yii\db\mssql\Schema') {
            if (stripos($append, 'IDENTITY') !== false && stripos($append, 'PRIMARY KEY') !== false) {
                return true;
            }
        } else {
            if (stripos($append, 'PRIMARY KEY') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes primary key and autoincrement string based on column properties and used schema.
     * @param string $append
     * @return string|bool|null
     * @since 2.1.2
     */
    public function removePrimaryKeyString($append)
    {
        if (!$this->findPrimaryKeyString($append)) {
            return null;
        }

        $uppercaseAppend = preg_replace('/\s+/', ' ', mb_strtoupper($append, 'UTF-8'));

        $schema = $this->db->schema;
        switch ($schema::className()) {
            case 'yii\db\mssql\Schema':
                $formattedAppend = str_replace(['PRIMARY KEY', 'IDENTITY'], '', $uppercaseAppend);
                break;
            case 'yii\db\oci\Schema':
            case 'yii\db\pgsql\Schema':
                $formattedAppend = str_replace('PRIMARY KEY', '', $uppercaseAppend);
                break;
            case 'yii\db\sqlite\Schema':
                $formattedAppend = str_replace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $uppercaseAppend);
                break;
            case 'yii\db\cubrid\Schema':
            case 'yii\db\mysql\Schema':
            default:
                $formattedAppend = str_replace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $uppercaseAppend);
        }

        return $formattedAppend ?: true;
    }

    /**
     * Returns size value from its structure.
     * @param array $column
     * @return mixed
     */
    public function renderSizeStructure($column)
    {
        return empty($column['length']) && !is_numeric($column['length']) ? null : $column['length'];
    }

    /**
     * Prepares updates definitions.
     * @return array
     */
    public function prepareUpdates()
    {
        $updates = [];
        /* @var $data array */
        foreach ($this->_modifications as $method => $data) {
            switch ($method) {
                case 'dropColumn':
                    foreach ($data as $column) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}'"];
                    }
                    break;
                case 'addColumn':
                    foreach ($data as $column => $type) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}', " . $this->renderColumnStructure($type)];
                    }
                    break;
                case 'alterColumn':
                    /* @var $typesList array */
                    foreach ($data as $column => $type) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}', " . $this->renderColumnStructure($type)];
                    }
                    break;
                case 'addForeignKey':
                    foreach ($data as $fk => $params) {
                        $definition = [
                            "'{$fk}'",
                            "'" . $this->generateTableName($this->tableName) . "'",
                            is_array($params[0]) ? '[' . implode(', ', $params[0]) . ']' : "'{$params[0]}'",
                            "'" . $this->generateTableName($params[1]) . "'",
                            is_array($params[2]) ? '[' . implode(', ', $params[2]) . ']' : "'{$params[2]}'",
                        ];
                        if ($params[3] !== null || $params[4] !== null) {
                            $definition[] = $params[3] !== null ? "'{$params[3]}'" : 'null';
                        }
                        if ($params[4] !== null) {
                            $definition[] = "'{$params[4]}'";
                        }
                        $updates[] = [$method, implode(', ', $definition)];
                    }
                    break;
                case 'dropForeignKey':
                    foreach ($data as $fk) {
                        $updates[] = [$method, "'{$fk}', '" . $this->generateTableName($this->tableName) . "'"];
                    }
                    break;
                case 'createIndex':
                    foreach ($data as $uidx => $columns) {
                        $updates[] = [$method, "'{$uidx}', '" . $this->generateTableName($this->tableName) . "', "
                            . (count($columns) === 1 ? "'{$columns[0]}'" : "['" . implode("', '", $columns) . "']") . ', true'];
                    }
                    break;
                case 'dropIndex':
                    foreach ($data as $uidx) {
                        $updates[] = [$method, "'{$uidx}', '" . $this->generateTableName($this->tableName) . "'"];
                    }
                    break;
                case 'dropPrimaryKey':
                    $updates[] = [$method, "'primary_key', '" . $this->generateTableName($this->tableName) . "'"];
                    break;
                case 'addPrimaryKey':
                    $updates[] = [$method, "'primary_key', '" . $this->generateTableName($this->tableName) . "', "
                        . (count($data) === 1 ? "'{$data[0]}'" : "['" . implode("', '", $data) . "']")];
            }
        }
        return $updates;
    }

    /**
     * Generates migration content or echoes exception message.
     * @return string
     * @throws InvalidParamException
     */
    public function generateMigration()
    {
        if (empty($this->_modifications)) {
            return parent::generateMigration();
        }
        $params = [
            'className' => $this->className,
            'methods' => $this->prepareUpdates(),
            'namespace' => !empty($this->namespace) ? FileHelper::normalizePath($this->namespace, '\\') : null
        ];
        return $this->view->renderFile(Yii::getAlias($this->templateFileUpdate), $params);
    }
}
