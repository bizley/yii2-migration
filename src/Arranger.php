<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ForeignKeyInterface;

use function array_diff;
use function array_key_exists;
use function array_merge_recursive;
use function array_unique;
use function array_values;
use function count;

final class Arranger implements ArrangerInterface
{
    /** @var TableMapperInterface */
    private $mapper;

    public function __construct(TableMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /** @var array<string, array<string>> */
    private $dependency = [];

    /** @param array<string> $inputTables */
    public function arrangeMigrations(array $inputTables): void
    {
        foreach ($inputTables as $inputTable) {
            $this->addDependency($inputTable);
            $foreignKeys = $this->mapper->getStructureOf($inputTable)->getForeignKeys();
            /** @var ForeignKeyInterface $foreignKey */
            foreach ($foreignKeys as $foreignKey) {
                $this->addDependency($inputTable, $foreignKey->getReferredTable());
            }
        }

        $this->arrangeTables($this->dependency);
    }

    private function addDependency(string $table, string $dependsOnTable = null): void
    {
        if (!array_key_exists($table, $this->dependency)) {
            $this->dependency[$table] = [];
        }

        if ($dependsOnTable) {
            $this->dependency[$table][] = $dependsOnTable;
        }
    }

    /** @var array<string> */
    private $tablesInOrder = [];

    public function getTablesInOrder(): array
    {
        return $this->tablesInOrder;
    }

    /** @var array<string, array<string>> */
    private $referencesToPostpone = [];

    /** @return array<string> */
    public function getReferencesToPostpone(): array
    {
        $flattenedReferencesToPostpone = [];
        $referencesToPostponeValues = array_values($this->referencesToPostpone);
        /** @var array<string> $referencesToPostponeValue */
        foreach ($referencesToPostponeValues as $referencesToPostponeValue) {
            foreach ($referencesToPostponeValue as $reference) {
                $flattenedReferencesToPostpone[] = $reference;
            }
        }

        return array_unique($flattenedReferencesToPostpone);
    }

    /** @param array<string, array<string>> $input */
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
                    $this->referencesToPostpone
                );
                $filteredDependencies = [];
                /** @var string $name */
                foreach ($postLinkMerged as $name => $dependencies) {
                    $filteredDependencies[$name] = array_unique($dependencies);
                }
                $this->referencesToPostpone = $filteredDependencies;
            }
        }

        $this->tablesInOrder = $order;
    }
}
