<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\BlueprintRendererInterface;
use bizley\migration\table\BlueprintInterface;
use ErrorException;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\helpers\FileHelper;

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

    /**
     * Returns the translated alias of update table migration template.
     */
    public function getUpdateTableMigrationTemplate(): string
    {
        /** @var string $translatedAlias */
        $translatedAlias = Yii::getAlias('@bizley/migration/views/migration.php');
        return $translatedAlias;
    }

    /**
     * Prepares a blueprint for update.
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @throws TableMissingException
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function prepareBlueprint(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): BlueprintInterface {
        if ($this->tableMapper->getTableSchema($tableName) === null) {
            throw new TableMissingException("Table '$tableName' does not exists!");
        }

        return $this->inspector->prepareBlueprint(
            $this->tableMapper->getStructureOf($tableName),
            $onlyShow,
            $migrationsToSkip,
            $migrationPaths,
            $this->tableMapper->getSchemaType(),
            $this->tableMapper->getEngineVersion()
        );
    }

    private function getNormalizedNamespace(?string $namespace): ?string
    {
        return !empty($namespace) ? FileHelper::normalizePath($namespace, '\\') : null;
    }

    /**
     * Generates migration based on the blueprint.
     * @throws NotSupportedException
     */
    public function generateFromBlueprint(
        BlueprintInterface $blueprint,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        return $this->view->renderFile(
            $this->getUpdateTableMigrationTemplate(),
            [
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'bodyUp' => $this->blueprintRenderer->renderUp(
                    $blueprint,
                    8,
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $usePrefix,
                    $dbPrefix
                ),
                'bodyDown' => $this->blueprintRenderer->renderDown(
                    $blueprint,
                    8,
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $usePrefix,
                    $dbPrefix
                )
            ]
        );
    }
}
