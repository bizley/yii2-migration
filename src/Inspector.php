<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\Blueprint;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureBuilderInterface;
use bizley\migration\table\StructureChangeInterface;
use bizley\migration\table\StructureInterface;
use ErrorException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use function array_key_exists;
use function array_reverse;
use function count;
use function in_array;
use function trim;

final class Inspector implements InspectorInterface
{
    /** @var HistoryManagerInterface */
    private $historyManager;

    /** @var ExtractorInterface */
    private $extractor;

    /** @var StructureBuilderInterface */
    private $structureBuilder;

    /** @var ComparatorInterface */
    private $comparator;

    public function __construct(
        HistoryManagerInterface $historyManager,
        ExtractorInterface $extractor,
        StructureBuilderInterface $structureBuilder,
        ComparatorInterface $comparator
    ) {
        $this->historyManager = $historyManager;
        $this->extractor = $extractor;
        $this->structureBuilder = $structureBuilder;
        $this->comparator = $comparator;
    }

    /** @var string */
    private $currentTable;

    /**
     * Prepares a blueprint for the upcoming update.
     * @param StructureInterface $newStructure
     * @param bool $onlyShow
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return BlueprintInterface
     * @throws InvalidConfigException
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function prepareBlueprint(
        StructureInterface $newStructure,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths,
        ?string $schema,
        ?string $engineVersion
    ): BlueprintInterface {
        $this->currentTable = $newStructure->getName();
        $history = $this->historyManager->fetchHistory();

        $blueprint = new Blueprint();
        $blueprint->setTableName($this->currentTable);

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

            if (count($this->appliedChanges)) {
                $this->comparator->compare(
                    $newStructure,
                    $this->structureBuilder->build(array_reverse($this->appliedChanges), $schema, $engineVersion),
                    $blueprint,
                    $onlyShow,
                    $schema,
                    $engineVersion
                );
            } else {
                $blueprint->startFromScratch();
            }
        } else {
            $blueprint->startFromScratch();
        }

        return $blueprint;
    }

    /** @var array<StructureChangeInterface> */
    private $appliedChanges = [];

    /**
     * Gathers the changes from migrations recursively.
     * @param array<string, array<StructureChangeInterface>>|null $changes
     * @return bool true if more data can be analysed or false if this must be last one
     * @throws InvalidConfigException
     */
    private function gatherChanges(?array $changes): bool
    {
        if ($changes === null || array_key_exists($this->currentTable, $changes) === false) {
            return true;
        }

        $data = array_reverse($changes[$this->currentTable]);

        /** @var StructureChangeInterface $change */
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
}
