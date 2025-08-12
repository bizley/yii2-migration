<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\base\NotSupportedException;

final class Arranger implements ArrangerInterface
{
    /** @var TableMapperInterface */
    private $mapper;

    public function __construct(TableMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /** @var array<string, array<string>> */
    private $dependencies = [];

    /**
     * Arranges the tables in proper order based on the presence of the foreign keys.
     * @param array<string> $inputTables
     * @throws NotSupportedException
     */
    public function arrangeTables(array $inputTables): void
    {
        $this->dependencies = [];
        $this->referencesToPostpone = [];
        $this->tablesInOrder = [];

        foreach ($inputTables as $inputTable) {
            $this->addDependency($inputTable);

            foreach ($this->mapper->getStructureOf($inputTable)->getForeignKeys() as $foreignKey) {
                $this->addDependency($inputTable, $foreignKey->getReferredTable());
            }
        }

        $this->arrangeDependencies($this->dependencies);
    }

    /**
     * Adds dependency of the table.
     */
    private function addDependency(string $table, ?string $dependsOnTable = null): void
    {
        if (!\array_key_exists($table, $this->dependencies)) {
            $this->dependencies[$table] = [];
        }

        if ($dependsOnTable) {
            $this->dependencies[$table][] = $dependsOnTable;
        }
    }

    /** @var array<string> */
    private $tablesInOrder = [];

    /**
     * Returns the tables in proper order.
     * @return array<string>
     */
    public function getTablesInOrder(): array
    {
        return $this->tablesInOrder;
    }

    /** @var array<string, array<string>> */
    private $referencesToPostpone = [];

    /**
     * Returns the references that needs to be postponed. Foreign keys referring the tables in references must be
     * added in migration after the migration creating all the tables.
     * @return array<string>
     */
    public function getReferencesToPostpone(): array
    {
        $flattenedReferencesToPostpone = [];
        foreach ($this->referencesToPostpone as $referencesToPostponeValue) {
            foreach ($referencesToPostponeValue as $reference) {
                $flattenedReferencesToPostpone[] = $reference;
            }
        }

        return \array_unique($flattenedReferencesToPostpone);
    }

    /**
     * Arranges the dependencies recursively.
     * @param array<string, array<string>> $input
     */
    private function arrangeDependencies(array $input): void
    {
        $order = [];
        $checkList = [];

        $inputCount = \count($input);

        while ($inputCount > \count($order)) {
            $done = false;
            $lastCheckedName = $lastCheckedDependency = null;

            foreach ($input as $name => $dependencies) {
                if (\array_key_exists($name, $checkList)) {
                    continue;
                }

                $resolved = true;

                foreach ($dependencies as $dependency) {
                    if (!\array_key_exists($dependency, $checkList)) {
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
                $input[$lastCheckedName] = \array_diff($input[$lastCheckedName], [$lastCheckedDependency]);

                $this->arrangeDependencies($input);
                $order = $this->getTablesInOrder();
                $postLinkMerged = array_merge_recursive(
                    [$lastCheckedName => [$lastCheckedDependency]],
                    $this->referencesToPostpone
                );
                $filteredDependencies = [];
                /** @var string $name */
                foreach ($postLinkMerged as $name => $dependencies) {
                    $filteredDependencies[$name] = $dependencies;
                }
                $this->referencesToPostpone = $filteredDependencies;
            }
        }

        $this->tablesInOrder = $order;
    }
}
