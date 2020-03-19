<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\UpdateInstructionsRendererInterface;
use Yii;
use yii\base\View;

final class Updater implements UpdaterInterface
{
    /** @var TableMapperInterface */
    private $tableMapper;

    /** @var View */
    private $view;

    /** @var UpdateInstructionsRendererInterface */
    private $instructionsRenderer;

    public function __construct(
        TableMapperInterface $tableMapper,
        UpdateInstructionsRendererInterface $instructionsRenderer,
        View $view
    ) {
        $this->tableMapper = $tableMapper;
        $this->instructionsRenderer = $instructionsRenderer;
        $this->view = $view;
    }

    public function getUpdateTableMigrationTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/update_migration.php');
    }

    public function generateForTable(
        string $tableName,
        string $migrationName,
        array $referencesToPostpone = [],
        bool $generalSchema = true,
        string $namespace = null
    ): string {
        if ($this->tableMapper->getTableSchema($tableName) === null) {
            throw new TableMissingException("Table $tableName does not exists.");
        }



        $this->instructionsRenderer->setPlan($this->tableMapper->getStructureOf($tableName, $referencesToPostpone));

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
