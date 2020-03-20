<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\BlueprintRendererInterface;
use bizley\migration\table\BlueprintInterface;
use Yii;
use yii\base\View;

final class Updater implements UpdaterInterface
{
    /** @var TableMapperInterface */
    private $tableMapper;

    /** @var View */
    private $view;

    /** @var BlueprintRendererInterface */
    private $blueprintRenderer;

    /** @var InspectorInterface */
    private $inspector;

    public function __construct(
        TableMapperInterface $tableMapper,
        InspectorInterface $inspector,
        BlueprintRendererInterface $blueprintRenderer,
        View $view
    ) {
        $this->tableMapper = $tableMapper;
        $this->inspector = $inspector;
        $this->blueprintRenderer = $blueprintRenderer;
        $this->view = $view;
    }

    public function getUpdateTableMigrationTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/update_migration.php');
    }

    /** @var BlueprintInterface */
    private $blueprint;

    /**
     * @param string $tableName
     * @param bool $onlyShow
     * @param array $migrationsToSkip
     * @param array $migrationPaths
     * @return bool
     * @throws TableMissingException
     */
    public function isUpdateRequired(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): bool {
        if ($this->tableMapper->getTableSchema($tableName) === null) {
            throw new TableMissingException("Table $tableName does not exists.");
        }

        $this->blueprint = $this->inspector->prepareBlueprint(
            $this->tableMapper->getStructureOf($tableName),
            $onlyShow,
            $migrationsToSkip,
            $migrationPaths,
            $this->tableMapper->getSchemaType(),
            $this->tableMapper->getEngineVersion()
        );

        return $this->blueprint->isPending();
    }

    /**
     * @param string $migrationName
     * @param bool $generalSchema
     * @param string|null $namespace
     * @return string
     */
    public function generateForPendingTable(
        string $migrationName,
        bool $generalSchema = true,
        string $namespace = null
    ): string {
        $this->blueprintRenderer->setBlueprint($this->blueprint);

        return $this->view->renderFile(
            $this->getUpdateTableMigrationTemplate(),
            [
                'tableName' => $this->structureRenderer->renderName($tableName),
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'body' => $this->structureRenderer->renderStructure(
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $generalSchema,
                    8
                )
            ]
        );
    }
}
