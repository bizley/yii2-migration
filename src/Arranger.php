<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\base\InvalidConfigException;
use yii\db\Connection;

use function array_diff;
use function array_key_exists;
use function array_merge_recursive;
use function array_unique;
use function count;

class Arranger implements ArrangerInterface
{
    /**
     * @var TableMapperInterface
     */
    private $mapper;

    /**
     * @var Connection
     */
    private $db;

    public function __construct(TableMapperInterface $mapper = null, Connection $db = null)
    {
        $this->mapper = $mapper;
        $this->db = $db;
    }

    public function setMapper(TableMapperInterface $mapper): void
    {
        $this->mapper = $mapper;
    }

    public function getMapper(): TableMapperInterface
    {
        return $this->mapper;
    }

    public function setDb(Connection $db): void
    {
        $this->db = $db;
    }

    public function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * @param array $inputTables
     * @throws InvalidConfigException
     */
    public function arrangeMigrations(array $inputTables): void
    {
        if ($this->db instanceof Connection === false) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\\db\\Connection!");
        }
        if ($this->mapper instanceof TableMapperInterface === false) {
            throw new InvalidConfigException(
                "Parameter 'generator' must implement bizley\\migration\\TableMapperInterface!"
            );
        }

        $mapper = $this->getMapper();

        foreach ($inputTables as $inputTable) {
            $this->addDependency($inputTable);
            $mapper->mapTable($inputTable);
            $foreignKeys = $mapper->getStructure()->getForeignKeys();
            foreach ($foreignKeys as $foreignKey) {
                $this->addDependency($inputTable, $foreignKey->refTable);
            }
        }

        $this->arrangeTables($this->dependency);
    }

    /**
     * @var array
     */
    private $dependency = [];

    private function addDependency(string $table, string $dependsOnTable = null): void
    {
        if (!array_key_exists($table, $this->dependency)) {
            $this->dependency[$table] = [];
        }

        if ($dependsOnTable) {
            $this->dependency[$table][] = $dependsOnTable;
        }
    }

    /**
     * @var array
     */
    private $tablesInOrder = [];

    public function getTablesInOrder(): array
    {
        return $this->tablesInOrder;
    }

    /**
     * @var array
     */
    private $suppressedForeignKeys = [];

    public function getSuppressedForeignKeys(): array
    {
        return $this->suppressedForeignKeys;
    }

    private function arrangeTables(array $input): void
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
