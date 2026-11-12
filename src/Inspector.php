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

    /** @var array<StructureChangeInterface> */
    private $appliedChanges = [];

    /** @var string */
    private $currentTable;

    /**
     * Changes extracted from migrations, indexed by migration name. Migration classes can be loaded only once per
     * process, so extracting the same migration again would always give the same result.
     * @var array<string, array<string, array<StructureChangeInterface>>|null>
     */
    private $extractedChanges = [];

    /**
     * Prepares a blueprint for the upcoming update.
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
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
        $this->appliedChanges = [];
        $history = $this->historyManager->fetchHistory();

        $blueprint = new Blueprint();
        $blueprint->setTableName($this->currentTable);

        if (!empty($history)) {
            foreach ($history as $migration => $time) {
                $migration = \trim($migration, '\\');
                if (\in_array($migration, $migrationsToSkip, true)) {
                    continue;
                }

                if (!$this->gatherChanges($this->extractChanges($migration, $migrationPaths))) {
                    break;
                }
            }

            if (!empty($this->appliedChanges)) {
                $this->comparator->compare(
                    $newStructure,
                    $this->structureBuilder->build(\array_reverse($this->appliedChanges), $schema, $engineVersion),
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

    /**
     * Returns the changes of the migration, extracting them only on the first call for the given migration.
     * @param array<string> $migrationPaths
     * @return array<string, array<StructureChangeInterface>>|null
     * @throws ErrorException
     */
    private function extractChanges(string $migration, array $migrationPaths): ?array
    {
        if (!\array_key_exists($migration, $this->extractedChanges)) {
            $this->extractor->extract($migration, $migrationPaths);
            $this->extractedChanges[$migration] = $this->extractor->getChanges();
        }

        return $this->extractedChanges[$migration];
    }

    /**
     * Gathers the changes from migrations recursively.
     * @param array<string, array<StructureChangeInterface>>|null $changes
     * @return bool true if more data can be analysed or false if this must be last one
     * @throws InvalidConfigException
     * @phpstan-impure
     */
    private function gatherChanges(?array $changes): bool
    {
        if ($changes === null || !\array_key_exists($this->currentTable, $changes)) {
            return true;
        }

        /** @var StructureChangeInterface $change */
        foreach (\array_reverse($changes[$this->currentTable]) as $change) {
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
