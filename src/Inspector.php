<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureBuilderInterface;
use bizley\migration\table\StructureChange;
use bizley\migration\table\StructureInterface;
use yii\base\InvalidConfigException;

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
     * @param StructureInterface $newStructure
     * @param bool $onlyShow
     * @param array $migrationsToSkip
     * @param array $migrationPaths
     * @return bool
     * @throws InvalidConfigException
     */
    public function isUpdateRequired(
        StructureInterface $newStructure,
        bool $onlyShow,
        array $migrationsToSkip = [],
        array $migrationPaths = []
    ): bool {
        $this->currentTable = $newStructure->getName();
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

            if (count($this->appliedChanges) === 0) {
                return true;
            }

            $this->structureBuilder->build(array_reverse($this->appliedChanges));
            $this->comparator->compare(
                $newStructure,
                $this->structureBuilder->getStructure(),
                $onlyShow
            );
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
}
