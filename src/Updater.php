<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\BlueprintRendererInterface;
use bizley\migration\table\BlueprintInterface;
use Yii;
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

    public function getUpdateTableMigrationTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/update_migration.php');
    }

    /**
     * @param string $tableName
     * @param bool $onlyShow
     * @param array $migrationsToSkip
     * @param array $migrationPaths
     * @return BlueprintInterface
     * @throws TableMissingException
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
     * @param BlueprintInterface $blueprint
     * @param string $migrationName
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
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
