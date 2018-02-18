<?php

namespace yii\db;

use bizley\migration\TableChange;
use yii\base\Component;
use yii\di\Instance;

/**
 * Dummy Migration class.
 * This class is used to gather migration details instead of applying them.
 */
class Migration extends Component implements MigrationInterface
{
    use SchemaBuilderTrait;

    /**
     * @var array List of all migration actions in form of 'table' => [array of changes]
     */
    public $changes = [];

    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
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
        $this->safeUp();
    }

    public function down()
    {
        return null;
    }

    public function safeUp() {}

    public function safeDown() {}

    /**
     * Returns extracted columns data.
     * @param array $columns
     * @return array
     * @throws \ReflectionException
     */
    protected function extractColumns($columns)
    {
        $schema = [];
        foreach ($columns as $name => $data) {
            $schema[$name] = $this->extractColumn($data);
        }
        return $schema;
    }

    /**
     * Returns type map for current schema query builder.
     * @param ColumnSchemaBuilder $type
     * @return array
     */
    public function getKeysMap($type)
    {
        return $type->db->schema->createQueryBuilder()->typeMap;
    }

    /**
     * Updates column properties based on schema type map.
     * @param string $type
     * @param array $keyToDb
     * @param array $dbToKey
     * @return array
     */
    public function fillTypeMapProperties($type, $keyToDb, $dbToKey)
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
            $schema['append'] = 'AUTO_INCREMENT';
            $builder = trim(str_replace('AUTO_INCREMENT', '', $builder));
        }
        if (strpos($builder, 'AUTOINCREMENT') !== false) {
            $schema['append'] = 'AUTOINCREMENT';
            $builder = trim(str_replace('AUTOINCREMENT', '', $builder));
        }
        if (strpos($builder, 'IDENTITY PRIMARY KEY') !== false) {
            $builder = trim(str_replace('IDENTITY PRIMARY KEY', '', $builder));
        }
        if (strpos($builder, 'PRIMARY KEY') !== false) {
            $builder = trim(str_replace('PRIMARY KEY', '', $builder));
        }
        if (strpos($builder, 'UNSIGNED') !== false) {
            $schema['isUnsigned'] = true;
            $builder = trim(str_replace('UNSIGNED', '', $builder));
        }
        preg_match('/^([a-z]+)(\(([0-9,]+)\))?$/', $builder, $matches);
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
     * @param ColumnSchemaBuilder $type
     * @return array
     * @throws \ReflectionException
     */
    protected function extractColumn($type)
    {
        $keyToDb = $this->getKeysMap($type);
        $dbToKey = $type->db->schema->typeMap;
        $properties = ['length', 'isNotNull', 'isUnique', 'check', 'default', 'append', 'isUnsigned'];
        $reflectionClass = new \ReflectionClass($type);
        $reflectionProperty = $reflectionClass->getProperty('type');
        $reflectionProperty->setAccessible(true);
        $schema = $this->fillTypeMapProperties($reflectionProperty->getValue($type), $keyToDb, $dbToKey);
        foreach ($properties as $property) {
            $reflectionProperty = $reflectionClass->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($type);
            if ($value !== null || !isset($schema[$property])) {
                $schema[$property] = $value;
            }
        }
        $schema['comment'] = empty($type->comment) ? '' : $type->comment;
        return $schema;
    }

    /**
     * Returns raw table name.
     * @param string $table
     * @return string
     */
    public function getRawTableName($table)
    {
        return $this->db->schema->getRawTableName($table);
    }

    /**
     * Adds method of structure change and its data
     * @param string $table
     * @param string $method
     * @param mixed $data
     */
    public function addChange($table, $method, $data)
    {
        $table = $this->getRawTableName($table);
        if (!isset($this->changes[$table])) {
            $this->changes[$table] = [];
        }
        $this->changes[$table][] = new TableChange(['table' => $table, 'method' => $method, 'data' => $data]);
    }

    /**
     * @inheritdoc
     */
    public function execute($sql, $params = [])
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function insert($table, $columns)
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function batchInsert($table, $columns, $rows)
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function delete($table, $condition = '', $params = [])
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function createTable($table, $columns, $options = null)
    {
        $this->addChange($table, 'createTable', $this->extractColumns($columns));
    }

    /**
     * @inheritdoc
     */
    public function renameTable($table, $newName)
    {
        $this->addChange($table, 'renameTable', $this->getRawTableName($newName));
    }

    /**
     * @inheritdoc
     */
    public function dropTable($table)
    {
        $this->addChange($table, 'dropTable', null);
    }

    /**
     * @inheritdoc
     */
    public function truncateTable($table)
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, $type)
    {
        $this->addChange($table, 'addColumn', [$column => $this->extractColumn($type)]);
    }

    /**
     * @inheritdoc
     */
    public function dropColumn($table, $column)
    {
        $this->addChange($table, 'dropColumn', $column);
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $name, $newName)
    {
        $this->addChange($table, 'renameColumn', [$name => $newName]);
    }

    /**
     * @inheritdoc
     */
    public function alterColumn($table, $column, $type)
    {
        $this->addChange($table, 'alterColumn', [$column => $this->extractColumn($type)]);
    }

    /**
     * @inheritdoc
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        $this->addChange($table, 'addPrimaryKey', [$name => is_array($columns) ? $columns : explode(',', $columns)]);
    }

    /**
     * @inheritdoc
     */
    public function dropPrimaryKey($name, $table)
    {
        $this->addChange($table, 'dropPrimaryKey', $name);
    }

    /**
     * @inheritdoc
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $this->addChange($table, 'addForeignKey', [
            $name,
            is_array($columns) ? $columns : explode(',', $columns),
            $refTable,
            is_array($refColumns) ? $refColumns : explode(',', $refColumns),
            $delete,
            $update
        ]);
    }

    /**
     * @inheritdoc
     */
    public function dropForeignKey($name, $table)
    {
        $this->addChange($table, 'dropForeignKey', $name);
    }

    /**
     * @inheritdoc
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        if ($unique) {
            $this->addChange($table, 'createIndex', [$name => is_array($columns) ? $columns : explode(',', $columns)]);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($name, $table)
    {
        // requires check for uniqueness
        $this->addChange($table, 'dropIndex', $name);
    }

    /**
     * @inheritdoc
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        $this->addChange($table, 'addCommentOnColumn', [$column => $comment]);
    }

    /**
     * @inheritdoc
     */
    public function addCommentOnTable($table, $comment)
    {
        // not supported
    }

    /**
     * @inheritdoc
     */
    public function dropCommentFromColumn($table, $column)
    {
        $this->addChange($table, 'dropCommentFromColumn', $column);
    }

    /**
     * @inheritdoc
     */
    public function dropCommentFromTable($table)
    {
        // not supported
    }
}
