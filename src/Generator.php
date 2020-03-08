<?php

declare(strict_types=1);

namespace bizley\migration;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\View;
use yii\db\Connection;
use yii\helpers\FileHelper;

use function is_array;

class Generator extends Component implements GeneratorInterface
{
    /** @var Connection DB connection */
    public $db;

    /** @var string Table name to be generated (before prefix) */
    public $tableName;

    /** @var string Migration class name */
    public $className;

    /** @var View View used in controller */
    public $view;

    /** @var bool Table prefix flag */
    public $useTablePrefix = true;

    /** @var string Create migration template file */
    public $templateFileCreate;

    /** @var string Update migration template file */
    public $templateFileUpdate;

    /** @var string|array Migration namespaces */
    public $namespace;

    /** @var bool Whether to use general column schema instead of database specific */
    public $generalSchema = true;

    /** @var string */
    public $tableOptionsInit;

    /** @var string */
    public $tableOptions;

    /** @var array */
    public $suppressForeignKey = [];

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->db instanceof Connection === false) {
            throw new InvalidConfigException("Parameter 'db' must be an instance of yii\\db\\Connection!");
        }

        if ($this->namespace !== null && is_array($this->namespace) === false) {
            $this->namespace = [$this->namespace];
        }
    }

    public function getNormalizedNamespace(): ?string
    {
        return !empty($this->namespace) ? FileHelper::normalizePath(reset($this->namespace), '\\') : null;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function generateMigration(): string
    {
        return $this->view->renderFile(
            Yii::getAlias($this->templateFileCreate),
            [
                'table' => $this->getTableStructure(),
                'className' => $this->className,
                'namespace' => $this->getNormalizedNamespace()
            ]
        );
    }
}
