<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\table\StructureChangeInterface;
use ErrorException;
use Yii;
use yii\base\InvalidArgumentException;
use yii\db\Connection;

use function file_exists;
use function strpos;

final class Extractor implements ExtractorInterface
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

    /** @var MigrationChangesInterface */
    private $subject;

    /**
     * Extracts migration data structures.
     * @param string $migration
     * @param array<string> $migrationPaths
     * @throws ErrorException
     */
    public function extract(string $migration, array $migrationPaths): void
    {
        $this->setDummyMigrationClass();

        if (strpos($migration, '\\') === false) { // not namespaced
            $fileFound = false;
            $file = null;
            foreach ($migrationPaths as $path) {
                /** @var string $file */
                $file = Yii::getAlias($path . DIRECTORY_SEPARATOR . $migration . '.php');
                if (file_exists($file)) {
                    $fileFound = true;
                    break;
                }
            }

            if (!$fileFound) {
                throw new ErrorException("File '{$migration}.php' can not be found! Check migration history table.");
            }

            require_once $file;
        }

        $this->subject = new $migration(['db' => $this->db, 'experimental' => $this->experimental]);
        if ($this->subject instanceof MigrationChangesInterface === false) {
            throw new ErrorException(
                "Class '{$migration}' must implement bizley\migration\dummy\MigrationChangesInterface."
            );
        }

        $this->subject->up();
    }

    /**
     * Sets the dummy migration file instead the real one to extract the migration structure instead of applying them.
     * It uses Yii's class map autoloaders hack.
     * @throws InvalidArgumentException
     */
    private function setDummyMigrationClass(): void
    {
        // attempt to register Yii's autoloader in case it's not been done already
        // registering it second time should be skipped anyway
        spl_autoload_register(['Yii', 'autoload'], true, true);

        Yii::$classMap['yii\db\Migration'] = Yii::getAlias('@bizley/migration/dummy/Migration.php');
    }

    /**
     * Returns the changes extracted from migrations.
     * @return array<string, array<StructureChangeInterface>>|null
     */
    public function getChanges(): ?array
    {
        return $this->subject !== null ? $this->subject->getChanges() : null;
    }
}
