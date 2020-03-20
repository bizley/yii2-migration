<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\StructureRendererInterface;
use Yii;
use yii\base\View;
use yii\helpers\FileHelper;

final class Generator implements GeneratorInterface
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

    public function getCreateTableMigrationTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/create_migration.php');
    }

    public function getCreateForeignKeysMigrationTemplate(): string
    {
        return Yii::getAlias('@bizley/migration/views/create_fk_migration.php');
    }

    private function getNormalizedNamespace(?string $namespace): ?string
    {
        return !empty($namespace) ? FileHelper::normalizePath($namespace, '\\') : null;
    }

    /**
     * @param string $tableName
     * @param string $migrationName
     * @param array $referencesToPostpone
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     * @throws TableMissingException
     */
    public function generateForTable(
        string $tableName,
        string $migrationName,
        array $referencesToPostpone = [],
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        if ($this->tableMapper->getTableSchema($tableName) === null) {
            throw new TableMissingException("Table $tableName does not exists.");
        }

        return $this->view->renderFile(
            $this->getCreateTableMigrationTemplate(),
            [
                'tableName' => $this->structureRenderer->renderName($tableName, $usePrefix, $dbPrefix),
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'body' => $this->structureRenderer->renderStructure(
                    $this->tableMapper->getStructureOf($tableName, $referencesToPostpone),
                    8,
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $usePrefix,
                    $dbPrefix
                )
            ]
        );
    }

    public function getSuppressedForeignKeys(): array
    {
        return $this->tableMapper->getSuppressedForeignKeys();
    }

    /**
     * @param array $foreignKeys
     * @param string $migrationName
     * @param string|null $namespace
     * @return string
     */
    public function generateForForeignKeys(
        array $foreignKeys,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string {
        return $this->view->renderFile(
            $this->getCreateForeignKeysMigrationTemplate(),
            [
                'tableName' => $this->structureRenderer->renderName($tableName, $usePrefix, $dbPrefix),
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'body' => $this->structureRenderer->get
            ]
        );
    }
}
