<?php

namespace yii\db;

use bizley\migration\table\Change;
use bizley\migration\table\Structure;
use ReflectionClass;
use ReflectionException;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\di\Instance;

use function array_key_exists;
use function get_class;
use function is_array;
use function preg_match;
use function preg_split;
use function str_replace;
use function strpos;
use function trim;

/**
 * Dummy Migration class.
 * This class is used to gather migration details instead of applying them.
 */
class Migration extends Component implements MigrationInterface
{
    use SchemaBuilderTrait;

    public $maxSqlOutputLength;
    public $compact = false;

    /**
     * @var array List of all migration actions in form of 'table' => [array of changes]
     */
    public $changes = [];

    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDb()
    {
        return $this->db;
    }

    public function up()
    {
        if ($this->safeUp() === false) {
            return false;
        }

        return null;
    }

    public function down()
    {
        return null;
    }

    public function safeUp()
    {
    }

    public function safeDown()
    {
    }

    /**
     * @param array $columns
     * @return array
     * @throws ReflectionException
     */
    protected function extractColumns(array $columns): array
    {
        $schema = [];

        foreach ($columns as $name => $data) {
            $schema[$name] = $this->extractColumn($data);
        }

        return $schema;
    }

    /**
     * Updates column properties based on schema type map.
     * @param string $type
     * @param array $keyToDb
     * @param array $dbToKey
     * @return array
     */
    protected function fillTypeMapProperties(string $type, array $keyToDb, array $dbToKey): array
    {
        $schema = [];

        if (!array_key_exists($type, $keyToDb)) {
            $schema['type'] = $type;
            return $schema;
        }

        $builder = $keyToDb[$type];

        if (strpos($builder, 'NOT NULL') !== false) {
            $schema['isNotNull'] = true;
            $builder = trim(str_replace('NOT NULL', '', $builder));
        }

        if (strpos($builder, 'AUTO_INCREMENT') !== false) {
            $schema['autoIncrement'] = true;
            $builder = trim(str_replace('AUTO_INCREMENT', '', $builder));
        }

        if (strpos($builder, 'AUTOINCREMENT') !== false) {
            $schema['autoIncrement'] = true;
            $builder = trim(str_replace('AUTOINCREMENT', '', $builder));
        }

        if (strpos($builder, 'IDENTITY PRIMARY KEY') !== false) {
            $schema['isPrimaryKey'] = true;
            $builder = trim(str_replace('IDENTITY PRIMARY KEY', '', $builder));
        }

        if (strpos($builder, 'PRIMARY KEY') !== false) {
            $schema['isPrimaryKey'] = true;
            $builder = trim(str_replace('PRIMARY KEY', '', $builder));
        }

        if (strpos($builder, 'UNSIGNED') !== false) {
            $schema['isUnsigned'] = true;
            $builder = trim(str_replace('UNSIGNED', '', $builder));
        }

        preg_match('/^([a-zA-Z ]+)(\(([0-9,]+)\))?$/', $builder, $matches);

        if (array_key_exists($matches[1], $dbToKey)) {
            if (!empty($matches[3])) {
                $schema['length'] = $matches[3];
            }

            $schema['type'] = $dbToKey[$matches[1]];
        }

        return $schema;
    }

    /**
     * Returns extracted column data.
     * @param ColumnSchemaBuilder $columnData
     * @return array
     * @throws ReflectionException
     * @throws InvalidArgumentException in case column data is not an instance of ColumnSchemaBuilder
     */
    protected function extractColumn(ColumnSchemaBuilder $columnData): array
    {
        if ($columnData instanceof ColumnSchemaBuilder === false) {
            throw new InvalidArgumentException(
                'Column data must be provided as an instance of yii\db\ColumnSchemaBuilder.'
            );
        }

        $reflectionClass = new ReflectionClass($columnData);
        $reflectionProperty = $reflectionClass->getProperty('type');
        $reflectionProperty->setAccessible(true);

        $schema = $this->fillTypeMapProperties(
            $reflectionProperty->getValue($columnData),
            $this->db->schema->createQueryBuilder()->typeMap,
            $this->db->schema->typeMap
        );

        foreach (
            [
                'length',
                'isNotNull',
                'isUnique',
                'check',
                'default',
                'append',
                'isUnsigned',
                'after',
                'isFirst'
            ] as $property
        ) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            $reflectionProperty->setAccessible(true);

            $value = $reflectionProperty->getValue($columnData);
            if (($value !== null && $value !== []) || array_key_exists($property, $schema) === false) {
                $schema[$property] = $value;
            }
        }

        $schema['comment'] = empty($columnData->comment) ? '' : $columnData->comment;

        return $schema;
    }

    public function getRawTableName(string $table): string
    {
        return $this->db->schema->getRawTableName($table);
    }

    /**
     * Adds method of structure change and its data
     * @param string $table
     * @param string $method
     * @param mixed $data
     */
    public function addChange(string $table, string $method, $data): void
    {
        $table = $this->getRawTableName($table);

        if (array_key_exists($table, $this->changes) === false) {
            $this->changes[$table] = [];
        }

        $this->changes[$table][]
            = new Change([
                'schema' => Structure::identifySchema(get_class($this->db->schema)),
                'table' => $table,
                'method' => $method,
                'data' => $data,
                'db' => $this->db,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql, $params = [])
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, $columns)
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function batchInsert($table, $columns, $rows)
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, $condition = '', $params = [])
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function createTable($table, $columns, $options = null)
    {
        $this->addChange($table, 'createTable', $this->extractColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable($table, $newName)
    {
        $this->addChange($table, 'renameTable', $this->getRawTableName($newName));
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable($table)
    {
        $this->addChange($table, 'dropTable', null);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTable($table)
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn($table, $column, $type)
    {
        $this->addChange($table, 'addColumn', [$column, $this->extractColumn($type)]);
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn($table, $column)
    {
        $this->addChange($table, 'dropColumn', $column);
    }

    /**
     * {@inheritdoc}
     */
    public function renameColumn($table, $name, $newName)
    {
        $this->addChange($table, 'renameColumn', [$name, $newName]);
    }

    /**
     * {@inheritdoc}
     */
    public function alterColumn($table, $column, $type)
    {
        $this->addChange($table, 'alterColumn', [$column, $this->extractColumn($type)]);
    }

    /**
     * {@inheritdoc}
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        $this->addChange(
            $table,
            'addPrimaryKey',
            [
                $name,
                is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns)
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dropPrimaryKey($name, $table)
    {
        $this->addChange($table, 'dropPrimaryKey', $name);
    }

    /**
     * {@inheritdoc}
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $this->addChange($table, 'addForeignKey', [
            $name,
            is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns),
            $refTable,
            is_array($refColumns) ? $refColumns : preg_split('/\s*,\s*/', $refColumns),
            $delete,
            $update
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($name, $table)
    {
        $this->addChange($table, 'dropForeignKey', $name);
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        $this->addChange(
            $table,
            'createIndex',
            [
                $name,
                is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns),
                $unique
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex($name, $table)
    {
        $this->addChange($table, 'dropIndex', $name);
    }

    /**
     * {@inheritdoc}
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        $this->addChange($table, 'addCommentOnColumn', [$column, $comment]);
    }

    /**
     * {@inheritdoc}
     */
    public function addCommentOnTable($table, $comment)
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function dropCommentFromColumn($table, $column)
    {
        $this->addChange($table, 'dropCommentFromColumn', $column);
    }

    /**
     * {@inheritdoc}
     */
    public function dropCommentFromTable($table)
    {
        // not supported
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($table, $insertColumns, $updateColumns = true, $params = [])
    {
        // not supported
    }
}
