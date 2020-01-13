<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Connection;

use function array_diff;
use function array_key_exists;
use function array_merge_recursive;
use function array_unique;
use function count;

class Arranger extends BaseObject
{
    /** @var Connection DB connection */
    public $db;

    /**
     * Checks if DB connection is passed.
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->db instanceof Connection === false) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\\db\\Connection!");
        }
    }

    protected function getGenerator(string $tableName): Generator
    {
        return new Generator([
            'db' => $this->db,
            'tableName' => $tableName,
        ]);
    }

    /**
     * @param array $inputTables
     * @throws InvalidConfigException
     */
    public function arrangeMigrations(array $inputTables): void
    {
        foreach ($inputTables as $inputTable) {
            $this->addDependency($inputTable);

            $tableStructure = $this->getGenerator($inputTable)->getTableStructure();
            foreach ($tableStructure->foreignKeys as $foreignKey) {
                $this->addDependency($inputTable, $foreignKey->refTable);
            }
        }

        $this->arrangeTables($this->dependency);
    }

    /** @var array */
    private $dependency = [];

    protected function addDependency(string $table, string $dependensOnTable = null): void
    {
        if (!array_key_exists($table, $this->dependency)) {
            $this->dependency[$table] = [];
        }

        if ($dependensOnTable) {
            $this->dependency[$table][] = $dependensOnTable;
        }
    }

    /** @var array */
    private $tablesInOrder = [];

    public function getTablesInOrder(): array
    {
        return $this->tablesInOrder;
    }

    /** @var array */
    private $suppressedForeignKeys = [];

    public function getSuppressedForeignKeys(): array
    {
        return $this->suppressedForeignKeys;
    }

    protected function arrangeTables(array $input): void
    {
        $order = [];
        $checkList = [];

        $inputCount = count($input);

        while ($inputCount > count($order)) {
            $done = false;
            $lastCheckedName = $lastCheckedDependency = null;

            foreach ($input as $name => $dependencies) {
                if (array_key_exists($name, $checkList)) {
                    continue;
                }

                $resolved = true;

                foreach ($dependencies as $dependency) {
                    if (!array_key_exists($dependency, $checkList)) {
                        $resolved = false;
                        $lastCheckedName = $name;
                        $lastCheckedDependency = $dependency;
                        break;
                    }
                }

                if ($resolved) {
                    $checkList[$name] = true;
                    $order[] = $name;

                    $done = true;
                }
            }

            if ($done === false) {
                $input[$lastCheckedName] = array_diff($input[$lastCheckedName], [$lastCheckedDependency]);

                $this->arrangeTables($input);
                $order = $this->getTablesInOrder();
                $postLinkMerged = array_merge_recursive(
                    [$lastCheckedName => [$lastCheckedDependency]],
                    $this->getSuppressedForeignKeys()
                );
                $filteredLink = [];
                foreach ($postLinkMerged as $name => $dependencies) {
                    $filteredLink[$name] = array_unique($dependencies);
                }
                $this->suppressedForeignKeys = $filteredLink;
            }
        }

        $this->tablesInOrder = $order;
    }
}
