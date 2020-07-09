<?php

namespace yii\db;

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\Schema;
use bizley\migration\SqlColumnMapper;
use bizley\migration\table\StructureChange;
use bizley\migration\table\StructureChangeInterface;
use ReflectionClass;
use ReflectionException;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;

use function array_key_exists;
use function is_array;
use function is_string;
use function preg_match;
use function preg_split;
use function str_replace;
use function strpos;
use function trim;

/**
 * Dummy Migration class.
 * This class is used to gather migration details instead of applying them.
 */
class Migration extends Component implements MigrationChangesInterface
{
    use SchemaBuilderTrait;

    public $maxSqlOutputLength;
    public $compact = false;

    /** @var array<StructureChangeInterface> List of all migration actions */
    private $changes = [];

    /** @var Connection|array|string */
    public $db;

    /** @var bool */
    public $experimental = false;

    /** @throws NotSupportedException */
    public function init()
    {
        parent::init();

        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

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
     * Extracts columns data.
     * @param array<string, mixed> $columns
     * @return array<string, array<string, string|int|null>>
     * @throws ReflectionException
     */
    private function extractColumns(array $columns): array
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
     * @param array<string, string> $keyToDb
     * @param array<string, string> $dbToKey
     * @return array
     */
    private function fillTypeMapProperties(string $type, array $keyToDb, array $dbToKey): array
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
     * @param mixed $columnData
     * @return array<string, string|int|null>
     * @throws ReflectionException
     * @throws InvalidArgumentException in case column data is not an instance of ColumnSchemaBuilder
     */
    private function extractColumn($columnData): array
    {
        if (Schema::identifySchema($this->db->schema) === Schema::OCI) {
            $typeMap = [
                'float' => 'double',
                'double' => 'double',
                'number' => 'decimal',
                'integer' => 'integer',
                'blob' => 'binary',
                'clob' => 'text',
                'timestamp' => 'timestamp',
                'string' => 'string',
            ];
        } else {
            $typeMap = $this->db->schema->typeMap;
        }

        if ($this->experimental && is_string($columnData)) {
            return SqlColumnMapper::map($columnData, $typeMap);
        }

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
            $this->db->schema->getQueryBuilder()->typeMap,
            $typeMap
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

    private function getRawTableName(string $table): string
    {
        return $this->db->schema->getRawTableName($table);
    }

    /**
     * Adds method of structure change and its data.
     * @param string $table
     * @param string $method
     * @param mixed $data
     */
    private function addChange(string $table, string $method, $data): void
    {
        $table = $this->getRawTableName($table);

        if (array_key_exists($table, $this->changes) === false) {
            $this->changes[$table] = [];
        }

        $change = new StructureChange();
        $change->setData($data);
        $change->setMethod($method);
        $change->setTable($table);

        $this->changes[$table][] = $change;
    }

    /** @return array<string, array<StructureChangeInterface>> */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function execute($sql, $params = [])
    {
        // not supported
    }

    public function insert($table, $columns)
    {
        // not supported
    }

    public function batchInsert($table, $columns, $rows)
    {
        // not supported
    }

    public function update($table, $columns, $condition = '', $params = [])
    {
        // not supported
    }

    public function delete($table, $condition = '', $params = [])
    {
        // not supported
    }

    public function upsert($table, $insertColumns, $updateColumns = true, $params = [])
    {
        // not supported
    }

    /** @throws ReflectionException */
    public function createTable($table, $columns, $options = null)
    {
        $this->addChange($table, 'createTable', $this->extractColumns($columns));
    }

    public function renameTable($table, $newName)
    {
        $this->addChange($table, 'renameTable', $this->getRawTableName($newName));
    }

    public function dropTable($table)
    {
        $this->addChange($table, 'dropTable', null);
    }

    public function truncateTable($table)
    {
        // not supported
    }

    /**
     * @throws ReflectionException
     */
    public function addColumn($table, $column, $type)
    {
        $this->addChange(
            $table,
            'addColumn',
            [
                'name' => $column,
                'schema' => $this->extractColumn($type)
            ]
        );
    }

    public function dropColumn($table, $column)
    {
        $this->addChange($table, 'dropColumn', $column);
    }

    public function renameColumn($table, $name, $newName)
    {
        $this->addChange(
            $table,
            'renameColumn',
            [
                'old' => $name,
                'new' => $newName
            ]
        );
    }

    /**
     * @throws ReflectionException
     */
    public function alterColumn($table, $column, $type)
    {
        $this->addChange(
            $table,
            'alterColumn',
            [
                'name' => $column,
                'schema' => $this->extractColumn($type)
            ]
        );
    }

    public function addPrimaryKey($name, $table, $columns)
    {
        $this->addChange(
            $table,
            'addPrimaryKey',
            [
                'name' => $name,
                'columns' => is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns)
            ]
        );
    }

    public function dropPrimaryKey($name, $table)
    {
        $this->addChange($table, 'dropPrimaryKey', $name);
    }

    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $columns = is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns);
        $this->addChange(
            $table,
            'addForeignKey',
            [
                'name' => $name,
                'columns' => $columns,
                'referredTable' => $this->getRawTableName($refTable),
                'referredColumns' => is_array($refColumns) ? $refColumns : preg_split('/\s*,\s*/', $refColumns),
                'onDelete' => $delete,
                'onUpdate' => $update,
                'tableName' => $this->getRawTableName($table)
            ]
        );
    }

    public function dropForeignKey($name, $table)
    {
        $this->addChange($table, 'dropForeignKey', $name);
    }

    public function createIndex($name, $table, $columns, $unique = false)
    {
        $this->addChange(
            $table,
            'createIndex',
            [
                'name' => $name,
                'columns' => is_array($columns) ? $columns : preg_split('/\s*,\s*/', $columns),
                'unique' => $unique
            ]
        );
    }

    public function dropIndex($name, $table)
    {
        $this->addChange($table, 'dropIndex', $name);
    }

    public function addCommentOnColumn($table, $column, $comment)
    {
        $this->addChange(
            $table,
            'addCommentOnColumn',
            [
                'column' => $column,
                'comment' => $comment
            ]
        );
    }

    public function addCommentOnTable($table, $comment)
    {
        // not supported
        // Yii is not fetching table's comment when gathering table's info so we can not compare new with old one
    }

    public function dropCommentFromColumn($table, $column)
    {
        $this->addChange($table, 'dropCommentFromColumn', $column);
    }

    public function dropCommentFromTable($table)
    {
        // not supported
        // Yii is not fetching table's comment when gathering table's info so we can not compare new with old one
    }
}
