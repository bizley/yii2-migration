<?php

namespace yii\db;

use bizley\migration\dummy\MigrationSqlInterface;
use Generator;
use yii\base\Component;
use yii\base\NotSupportedException;

/**
 * Dummy Migration class.
 * This class is used to gather migration SQL statements instead of applying them.
 */
class Migration extends Component implements MigrationSqlInterface
{
    use SchemaBuilderTrait;

    /** @var int|null */
    public $maxSqlOutputLength;
    /** @var bool */
    public $compact = false;

    /** @var string[] List of all migration SQL statements */
    private $statements = [];

    /** @var Connection */
    public $db;

    /** @throws NotSupportedException */
    public function init(): void
    {
        parent::init();

        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

    protected function getDb(): Connection
    {
        return $this->db;
    }

    /** @return mixed|void */
    public function up()
    {
        return $this->safeUp() !== false;
    }

    /** @return mixed|void */
    public function down()
    {
        return $this->safeDown() !== false;
    }

    /** @return mixed|void */
    public function safeUp()
    {
    }

    /** @return mixed|void */
    public function safeDown()
    {
    }

    /** @return string[] */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /** @param string[] $params */
    public function execute(string $sql, array $params = []): void
    {
        $this->statements[] = $this->db->createCommand($sql)->bindValues($params)->getRawSql();
    }

    /** @param array<string, mixed>|Query $columns */
    public function insert(string $table, $columns): void
    {
        $this->statements[] = $this->db->createCommand()->insert($table, $columns)->getRawSql();
    }

    /**
     * @param string[] $columns
     * @param array<mixed>|Generator $rows
     */
    public function batchInsert(string $table, array $columns, $rows): void
    {
        $this->statements[] = $this->db->createCommand()->batchInsert($table, $columns, $rows)->getRawSql();
    }

    /**
     * @param array<string, mixed> $columns
     * @param string|array<mixed> $condition
     * @param string[] $params
     */
    public function update(string $table, array $columns, $condition = '', array $params = []): void
    {
        $this->statements[] = $this->db->createCommand()->update($table, $columns, $condition, $params)->getRawSql();
    }

    /**
     * @param string|array<mixed> $condition
     * @param string[] $params
     */
    public function delete(string $table, $condition = '', array $params = []): void
    {
        $this->statements[] = $this->db->createCommand()->delete($table, $condition, $params)->getRawSql();
    }

    /**
     * @param array<string, mixed>|Query $insertColumns
     * @param array<string, mixed>|bool $updateColumns
     * @param string[] $params
     */
    public function upsert(string $table, $insertColumns, $updateColumns = true, array $params = []): void
    {
        $this->statements[] = $this->db
            ->createCommand()
            ->upsert($table, $insertColumns, $updateColumns, $params)
            ->getRawSql();
    }

    /** @param array<string, string|ColumnSchemaBuilder> $columns */
    public function createTable(string $table, array $columns, ?string $options = null): void
    {
        $this->statements[] = $this->db->createCommand()->createTable($table, $columns, $options)->getRawSql();
        foreach ($columns as $column => $type) {
            if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
                $this->statements[] = $this->db
                    ->createCommand()
                    ->addCommentOnColumn($table, $column, $type->comment)
                    ->getRawSql();
            }
        }
    }

    public function renameTable(string $table, string $newName): void
    {
        $this->statements[] = $this->db->createCommand()->renameTable($table, $newName)->getRawSql();
    }

    public function dropTable(string $table): void
    {
        $this->statements[] = $this->db->createCommand()->dropTable($table)->getRawSql();
    }

    public function truncateTable(string $table): void
    {
        $this->statements[] = $this->db->createCommand()->truncateTable($table)->getRawSql();
    }

    /** @param string|ColumnSchemaBuilder $type */
    public function addColumn(string $table, string $column, $type): void
    {
        $this->statements[] = $this->db->createCommand()->addColumn($table, $column, $type)->getRawSql();
        if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
            $this->statements[] = $this->db
                ->createCommand()
                ->addCommentOnColumn($table, $column, $type->comment)
                ->getRawSql();
        }
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->statements[] = $this->db->createCommand()->dropColumn($table, $column)->getRawSql();
    }

    public function renameColumn(string $table, string $name, string $newName): void
    {
        $this->statements[] = $this->db->createCommand()->renameColumn($table, $name, $newName)->getRawSql();
    }

    /** @param string|ColumnSchemaBuilder $type */
    public function alterColumn(string $table, string $column, $type): void
    {
        $this->statements[] = $this->db->createCommand()->alterColumn($table, $column, $type)->getRawSql();
        if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
            $this->statements[] = $this->db
                ->createCommand()
                ->addCommentOnColumn($table, $column, $type->comment)
                ->getRawSql();
        }
    }

    /** @param string|string[] $columns */
    public function addPrimaryKey(string $name, string $table, $columns): void
    {
        $this->statements[] = $this->db->createCommand()->addPrimaryKey($name, $table, $columns)->getRawSql();
    }

    public function dropPrimaryKey(string $name, string $table): void
    {
        $this->statements[] = $this->db->createCommand()->dropPrimaryKey($name, $table)->getRawSql();
    }

    /**
     * @param string|string[] $columns
     * @param string|string[] $refColumns
     */
    public function addForeignKey(
        string $name,
        string $table,
        $columns,
        string $refTable,
        $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): void {
        $this->statements[] = $this->db
            ->createCommand()
            ->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update)
            ->getRawSql();
    }

    public function dropForeignKey(string $name, string $table): void
    {
        $this->statements[] = $this->db->createCommand()->dropForeignKey($name, $table)->getRawSql();
    }

    /** @param string|string[] $columns */
    public function createIndex(string $name, string $table, $columns, bool $unique = false): void
    {
        $this->statements[] = $this->db->createCommand()->createIndex($name, $table, $columns, $unique)->getRawSql();
    }

    public function dropIndex(string $name, string $table): void
    {
        $this->statements[] = $this->db->createCommand()->dropIndex($name, $table)->getRawSql();
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): void
    {
        $this->statements[] = $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->getRawSql();
    }

    public function addCommentOnTable(string $table, string $comment): void
    {
        $this->statements[] = $this->db->createCommand()->addCommentOnTable($table, $comment)->getRawSql();
    }

    public function dropCommentFromColumn(string $table, string $column): void
    {
        $this->statements[] = $this->db->createCommand()->dropCommentFromColumn($table, $column)->getRawSql();
    }

    public function dropCommentFromTable(string $table): void
    {
        $this->statements[] = $this->db->createCommand()->dropCommentFromTable($table)->getRawSql();
    }
}
