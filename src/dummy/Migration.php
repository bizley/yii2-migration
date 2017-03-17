<?php

namespace yii\db;

use yii\base\Component;
use yii\di\Instance;

/**
 * Dummy Migration class.
 */
class Migration extends Component implements MigrationInterface
{
    use SchemaBuilderTrait;

    /**
     *
     * @var array table => [array of changes]
     */
    public $changes = [];

    /**
     * @inheritdoc
     */
    public $db = 'db';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

    /**
     * @inheritdoc
     */
    protected function getDb()
    {
        return $this->db;
    }

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->safeUp();
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function safeUp() {}

    /**
     * @inheritdoc
     */
    public function safeDown() {}

    /**
     * Returns extracted columns data.
     * @param array $columns
     * @return array
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
        $builder = $type->db->schema->createQueryBuilder();
        return $builder->typeMap;
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
            //$schema['append'] = 'IDENTITY PRIMARY KEY';
            $builder = trim(str_replace('IDENTITY PRIMARY KEY', '', $builder));
        }
        if (strpos($builder, 'PRIMARY KEY') !== false) {
            //$schema['append'] = 'PRIMARY KEY';
            $builder = trim(str_replace('PRIMARY KEY', '', $builder));
        }
        if (strpos($builder, 'UNSIGNED') !== false) {
            $schema['isUnsigned'] = true;
            $builder = trim(str_replace('UNSIGNED', '', $builder));
        }
        preg_match('/^([a-z]+)(\(([0-9,]+)\))?$/', $builder, $matches);
        if (array_key_exists($matches[1], $dbToKey)) {
            if (isset($matches[3]) && $matches[3]) {
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
        $schema['comment'] = $type->comment;
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
     * @param mixed $value
     */
    public function addChanges($table, $method, $value)
    {
        $table = $this->getRawTableName($table);
        if (!isset($this->changes[$table])) {
            $this->changes[$table] = [];
        }
        $this->changes[$table][$method] = $value;
    }

    /**
     * Executes a SQL statement.
     * This method executes the specified SQL statement using [[db]].
     * @param string $sql the SQL statement to be executed
     * @param array $params input parameters (name => value) for the SQL execution.
     * See [[Command::execute()]] for more details.
     */
    public function execute($sql, $params = [])
    {
        echo "    > execute SQL: $sql ...";
        $time = microtime(true);
        $this->db->createCommand($sql)->bindValues($params)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * @inheritdoc
     */
    public function insert($table, $columns) {}

    /**
     * @inheritdoc
     */
    public function batchInsert($table, $columns, $rows) {}

    /**
     * @inheritdoc
     */
    public function update($table, $columns, $condition = '', $params = []) {}

    /**
     * @inheritdoc
     */
    public function delete($table, $condition = '', $params = []) {}

    /**
     * @inheritdoc
     */
    public function createTable($table, $columns, $options = null)
    {
        $this->addChanges($table, 'createTable', $this->extractColumns($columns));
    }

    /**
     * @inheritdoc
     */
    public function renameTable($table, $newName)
    {
        $this->addChanges($table, 'renameTable', $this->getRawTableName($newName));
    }

    /**
     * @inheritdoc
     */
    public function dropTable($table)
    {
        $this->addChanges($table, 'dropTable', null);
    }

    /**
     * @inheritdoc
     */
    public function truncateTable($table) {}

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, $type)
    {
        $this->addChanges($table, 'addColumn', [$column => $this->extractColumn($type)]);
    }

    /**
     * @inheritdoc
     */
    public function dropColumn($table, $column)
    {
        $this->addChanges($table, 'dropColumn', ['___drop___' => $column]);
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $name, $newName)
    {
        $this->addChanges($table, 'renameColumn', ['___rename___' => [$name, $newName]]);
    }

    /**
     * @inheritdoc
     */
    public function alterColumn($table, $column, $type)
    {
        $this->addChanges($table, 'alterColumn', ['___alter___' => [$column, $this->extractColumn($type)]]);
    }

    /**
     * @inheritdoc
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        $this->addChanges($table, 'addPrimaryKey', ['___primary___' => is_array($columns) ? $columns : explode(',', $columns)]);
    }

    /**
     * @inheritdoc
     */
    public function dropPrimaryKey($name, $table)
    {
        $this->addChanges($table, 'dropPrimaryKey', ['___dropprimary___' => $name]);
    }

    /**
     * @inheritdoc
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $this->addChanges($table, 'addForeignKey', ['___foreign___' => [
            $name,
            is_array($columns) ? $columns : explode(',', $columns),
            $refTable,
            is_array($refColumns) ? $refColumns : explode(',', $refColumns),
            $delete,
            $update
        ]]);
    }

    /**
     * @inheritdoc
     */
    public function dropForeignKey($name, $table)
    {
        $this->addChanges($table, 'dropForeignKey', ['___dropforeign___' => $name]);
    }

    /**
     * @inheritdoc
     */
    public function createIndex($name, $table, $columns, $unique = false) {}

    /**
     * @inheritdoc
     */
    public function dropIndex($name, $table) {}

    /**
     * @inheritdoc
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        $this->addChanges($table, 'addCommentOnColumn', ['___comment___' => [$column, $comment]]);
    }

    /**
     * @inheritdoc
     */
    public function addCommentOnTable($table, $comment) {}

    /**
     * @inheritdoc
     */
    public function dropCommentFromColumn($table, $column)
    {
        $this->addChanges($table, 'dropCommentFromColumn', ['___dropcomment___' => $column]);
    }

    /**
     * @inheritdoc
     */
    public function dropCommentFromTable($table) {}
}
