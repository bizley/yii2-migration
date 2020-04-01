<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\renderers\StructureRendererInterface;
use bizley\migration\table\ForeignKeyInterface;
use Yii;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\helpers\FileHelper;

use function array_reverse;

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

    /**
     * Returns the translated alias of create table migration template.
     * @return string
     */
    public function getCreateTableMigrationTemplate(): string
    {
        /** @var string $translatedAlias */
        $translatedAlias = Yii::getAlias('@bizley/migration/views/migration.php');
        return $translatedAlias;
    }

    /**
     * Returns the translated alias of create foreign keys migration template.
     * @return string
     */
    public function getCreateForeignKeysMigrationTemplate(): string
    {
        /** @var string $translatedAlias */
        $translatedAlias = Yii::getAlias('@bizley/migration/views/migration.php');
        return $translatedAlias;
    }

    /**
     * Returns the normalized namespace (in case it uses incorrect slashes).
     * @param string|null $namespace
     * @return string|null
     */
    private function getNormalizedNamespace(?string $namespace): ?string
    {
        return !empty($namespace) ? FileHelper::normalizePath($namespace, '\\') : null;
    }

    /**
     * Generates migration for the table.
     * @param string $tableName
     * @param string $migrationName
     * @param array<string> $referencesToPostpone
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     * @throws TableMissingException
     * @throws NotSupportedException
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
            throw new TableMissingException("Table '$tableName' does not exists!");
        }

        $structure = $this->tableMapper->getStructureOf($tableName, $referencesToPostpone);

        return $this->view->renderFile(
            $this->getCreateTableMigrationTemplate(),
            [
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'bodyUp' => $this->structureRenderer->renderStructureUp(
                    $structure,
                    8,
                    $this->tableMapper->getSchemaType(),
                    $this->tableMapper->getEngineVersion(),
                    $usePrefix,
                    $dbPrefix
                ),
                'bodyDown' => $this->structureRenderer->renderStructureDown($structure, 8, $usePrefix, $dbPrefix)
            ]
        );
    }

    /**
     * Returns the suppressed foreign keys that needs to be added later when generating migrations.
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array
    {
        return $this->tableMapper->getSuppressedForeignKeys();
    }

    /**
     * Generates the migration for the foreign keys.
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param string $migrationName
     * @param bool $usePrefix
     * @param string $dbPrefix
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
                'className' => $migrationName,
                'namespace' => $this->getNormalizedNamespace($namespace),
                'bodyUp' => $this->structureRenderer->renderForeignKeysUp(
                    $foreignKeys,
                    8,
                    $usePrefix,
                    $dbPrefix
                ),
                'bodyDown' => $this->structureRenderer->renderForeignKeysDown(
                    array_reverse($foreignKeys),
                    8,
                    $usePrefix,
                    $dbPrefix
                )
            ]
        );
    }
}
