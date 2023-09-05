<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\dummy\MigrationSqlInterface;
use bizley\migration\table\StructureChangeInterface;
use ErrorException;
use Yii;
use yii\base\InvalidArgumentException;
use yii\db\Connection;

final class Extractor implements SqlExtractorInterface
{
    /** @var Connection */
    private $db;

    /**
     * @var bool
     * @since 4.1.0
     */
    private $experimental;

    public function __construct(Connection $db, bool $experimental = false)
    {
        $this->db = $db;
        $this->experimental = $experimental;
    }

    /** @var MigrationChangesInterface|MigrationSqlInterface */
    private $subject;

    /**
     * Extracts migration data structures.
     * @param string $migration
     * @param string[] $migrationPaths
     * @throws ErrorException
     */
    public function extract(string $migration, array $migrationPaths): void
    {
        $this->setDummyMigrationClass();
        $this->loadFile($migration, $migrationPaths);

        $subject = new $migration(['db' => $this->db, 'experimental' => $this->experimental]);
        if (!$subject instanceof MigrationChangesInterface) {
            throw new ErrorException(
                "Class '{$migration}' must implement bizley\migration\dummy\MigrationChangesInterface."
            );
        }

        $this->subject = $subject;
        $this->subject->up();
    }

    /**
     * Loads a non-namespaced file.
     * @param string $migration
     * @param string[] $migrationPaths
     * @throws ErrorException
     */
    private function loadFile(string $migration, array $migrationPaths): void
    {
        if (\strpos($migration, '\\') !== false) {
            // migration with `\` character is most likely namespaced, so it doesn't require loading
            return;
        }

        foreach ($migrationPaths as $path) {
            /** @var string $file */
            $file = Yii::getAlias($path . DIRECTORY_SEPARATOR . $migration . '.php');
            if (\file_exists($file)) {
                require_once $file;

                return;
            }
        }

        throw new ErrorException("File '{$migration}.php' can not be found!");
    }

    /**
     * Sets the dummy migration file instead the real one to extract the migration structure instead of applying them.
     * It uses Yii's class map autoloaders hack.
     * @throws InvalidArgumentException
     */
    private function setDummyMigrationClass(bool $sqlDummy = false): void
    {
        // attempt to register Yii's autoloader in case it's not been done already
        // registering it second time should be skipped anyway
        /** @infection-ignore-all */
        \spl_autoload_register(['Yii', 'autoload'], true, true);

        Yii::$classMap['yii\db\Migration'] = Yii::getAlias(
            $sqlDummy ? '@bizley/migration/dummy/MigrationSql.php' : '@bizley/migration/dummy/MigrationChanges.php'
        );
    }

    /**
     * Returns the changes extracted from migrations.
     * @return array<string, array<StructureChangeInterface>>|null
     */
    public function getChanges(): ?array
    {
        return $this->subject instanceof MigrationChangesInterface ? $this->subject->getChanges() : null;
    }

    /**
     * Extracts migration SQL statements.
     * @param string[] $migrationPaths
     * @throws ErrorException
     * @since 4.4.0
     */
    public function getSql(string $migration, array $migrationPaths, string $method): void
    {
        $this->setDummyMigrationClass(true);
        $this->loadFile($migration, $migrationPaths);

        $subject = new $migration(['db' => $this->db]);
        if ($subject instanceof MigrationSqlInterface === false) {
            throw new ErrorException(
                "Class '{$migration}' must implement bizley\migration\dummy\MigrationSqlInterface."
            );
        }

        $this->subject = $subject;
        $this->subject->{$method}();
    }

    /**
     * Returns the SQL statements extracted from migrations.
     * @return string[]
     * @since 4.4.0
     */
    public function getStatements(): array
    {
        return $this->subject instanceof MigrationSqlInterface ? $this->subject->getStatements() : [];
    }
}
