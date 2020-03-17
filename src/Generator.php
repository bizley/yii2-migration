<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\StructureRendererInterface;
use Yii;
use yii\base\View;
use yii\helpers\FileHelper;

class Generator implements GeneratorInterface
{
    /** @var TableMapperInterface */
    private $tableMapper;

    /** @var View View used in controller */
    public $view;

    /** @var StructureRendererInterface */
    private $structureRenderer;

    public function __construct(
        TableMapperInterface $tableMapper,
        StructureRendererInterface $structureRenderer,
        View $view
    ) {
        $this->tableMapper = $tableMapper;
        $this->structureRenderer = $structureRenderer;
        $this->view = $view;
    }

    public function getTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/create_migration.php');
    }

    private function getNormalizedNamespace(?string $namespace): ?string
    {
        return !empty($namespace) ? FileHelper::normalizePath($namespace, '\\') : null;
    }

    /**
     * @param string $tableName
     * @param string $migrationName
     * @param bool $generalSchema
     * @param string|null $namespace
     * @return string
     * @throws TableMissingException
     */
    public function generateFor(
        string $tableName,
        string $migrationName,
        bool $generalSchema = true,
        string $namespace = null
    ): string {
        if ($this->tableMapper->getTableSchema($tableName) === null) {
            throw new TableMissingException("Table $tableName does not exists.");
        }

        $this->structureRenderer->setStructure($this->tableMapper->getStructureOf($tableName));

        return $this->view->renderFile(
            $this->getTemplate(),
            [
                'body' => $this->structureRenderer->render(
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $generalSchema,
                    8
                ),
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace)
            ]
        );
    }
}
