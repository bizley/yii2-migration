<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ColumnFactory;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\Index;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\Structure;
use bizley\migration\table\StructureInterface;
use PDO;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\cubrid\Schema as CubridSchema;
use yii\db\mssql\Schema as MssqlSchema;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\oci\Schema as OciSchema;
use yii\db\pgsql\Schema as PgsqlSchema;
use yii\db\sqlite\Schema as SqliteSchema;
use yii\db\TableSchema;

final class TableMapper implements TableMapperInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    /** @var array<ForeignKeyInterface> */
    private array $suppressedForeignKeys = [];

    /**
     * Table metadata read from the database, indexed by table name. The table is read only once per mapper instance,
     * while the structure objects are built anew on every call because they can be modified later.
     * @var array<string, TableSchema|null>
     */
    private array $tableSchemas = [];

    /** @var array<string, array<ForeignKeyConstraint>> */
    private array $foreignKeyConstraints = [];

    /** @var array<string, array<IndexConstraint>> */
    private array $indexConstraints = [];

    /** @var array<string, Constraint|null> */
    private array $primaryKeyConstraints = [];

    /** @var array<string, array<string, string>> */
    private array $pgsqlIndexMethods = [];

    /**
     * Returns a structure of the table.
     * @param array<string> $referencesToPostpone
     * @throws NotSupportedException
     */
    public function getStructureOf(string $table, array $referencesToPostpone = []): StructureInterface
    {
        $this->suppressedForeignKeys = [];
        $foreignKeys = $this->getForeignKeys($table);

        foreach ($foreignKeys as $foreignKeyName => $foreignKey) {
            if (\in_array($foreignKey->getReferredTable(), $referencesToPostpone, true)) {
                $this->suppressedForeignKeys[] = $foreignKey;
                unset($foreignKeys[$foreignKeyName]);
            }
        }

        $indexes = $this->getIndexes($table);

        $structure = new Structure();
        $structure->setName($table);
        $structure->setPrimaryKey($this->getPrimaryKey($table));
        $structure->setForeignKeys($foreignKeys);
        $structure->setIndexes($indexes);
        $structure->setColumns($this->getColumns($table, $indexes));

        return $structure;
    }

    /**
     * Returns the foreign keys of the table.
     * @return array<ForeignKeyInterface>
     * @throws NotSupportedException
     */
    private function getForeignKeys(string $table): array
    {
        $mappedForeignKeys = [];
        if (!\array_key_exists($table, $this->foreignKeyConstraints)) {
            /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
            $schema = $this->db->getSchema();
            $this->foreignKeyConstraints[$table] = $schema->getTableForeignKeys($table, true);
        }

        foreach ($this->foreignKeyConstraints[$table] as $foreignKey) {
            $mappedForeignKey = new ForeignKey();
            $mappedForeignKey->setTableName($table);
            $mappedForeignKey->setName($foreignKey->name);
            $mappedForeignKey->setColumns($foreignKey->columnNames);
            $mappedForeignKey->setReferredTable($foreignKey->foreignTableName);
            $mappedForeignKey->setReferredColumns($foreignKey->foreignColumnNames);
            $mappedForeignKey->setOnDelete($foreignKey->onDelete);
            $mappedForeignKey->setOnUpdate($foreignKey->onUpdate);

            $mappedForeignKeys[$mappedForeignKey->getName()] = $mappedForeignKey;
        }

        return $mappedForeignKeys;
    }

    /**
     * Returns the indexes of the table.
     * @return array<IndexInterface>
     * @throws NotSupportedException
     */
    private function getIndexes(string $table): array
    {
        $mappedIndexes = [];
        if (!\array_key_exists($table, $this->indexConstraints)) {
            /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
            $schema = $this->db->getSchema();
            $this->indexConstraints[$table] = $schema->getTableIndexes($table, true);
        }

        foreach ($this->indexConstraints[$table] as $index) {
            if ($index->isPrimary === false && $index->name !== null) {
                $mappedIndex = new Index();
                $mappedIndex->setName($index->name);
                $mappedIndex->setUnique($index->isUnique);
                $mappedIndex->setColumns($index->columnNames);

                $mappedIndexes[$index->name] = $mappedIndex;
            }
        }

        if ($mappedIndexes !== [] && $this->getSchemaType() === Schema::PGSQL) {
            $methods = $this->getPgsqlIndexMethods($table);
            foreach ($mappedIndexes as $name => $mappedIndex) {
                $mappedIndex->setMethod($methods[$name] ?? null);
            }
        }

        return $mappedIndexes;
    }

    /**
     * Yii does not read the PostgreSQL index method, so it is taken from pg_am. Only the methods of indexes that can
     * be recreated with createIndex() are returned (index without predicate, expressions, and non-default operator
     * classes) - for others the method alone would not be enough (e.g. GIN index on a varchar column requires
     * the operator class) and the generated migration would fail.
     * @return array<string, string> non-B-tree index methods indexed by index names
     */
    private function getPgsqlIndexMethods(string $table): array
    {
        if (!\array_key_exists($table, $this->pgsqlIndexMethods)) {
            $this->pgsqlIndexMethods[$table] = [];

            $tableSchema = $this->getTableSchema($table);
            if ($tableSchema !== null) {
                $rows = $this->db->createCommand(
                    <<<'SQL'
SELECT
    "ic"."relname" AS "name",
    "am"."amname" AS "method",
    (
        "i"."indpred" IS NULL
        AND "i"."indexprs" IS NULL
        AND NOT EXISTS (
            SELECT 1 FROM "pg_opclass" AS "o" WHERE "o"."oid" = ANY("i"."indclass"::oid[]) AND NOT "o"."opcdefault"
        )
    ) AS "is_plain"
FROM "pg_index" AS "i"
INNER JOIN "pg_class" AS "ic" ON "ic"."oid" = "i"."indexrelid"
INNER JOIN "pg_am" AS "am" ON "am"."oid" = "ic"."relam"
INNER JOIN "pg_class" AS "tc" ON "tc"."oid" = "i"."indrelid"
INNER JOIN "pg_namespace" AS "n" ON "n"."oid" = "tc"."relnamespace"
WHERE "n"."nspname" = :schema AND "tc"."relname" = :table AND "am"."amname" <> 'btree'
SQL,
                    [':schema' => $tableSchema->schemaName, ':table' => $tableSchema->name]
                )->queryAll();

                foreach ($rows as $row) {
                    if (\in_array($row['is_plain'], [true, 1, '1', 't'], true)) {
                        $this->pgsqlIndexMethods[$table][$row['name']] = $row['method'];
                    }
                }
            }
        }

        return $this->pgsqlIndexMethods[$table];
    }

    /**
     * Returns a primary key of the table.
     * @throws NotSupportedException
     */
    private function getPrimaryKey(string $table): ?PrimaryKeyInterface
    {
        $primaryKey = null;

        if (!\array_key_exists($table, $this->primaryKeyConstraints)) {
            /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
            $schema = $this->db->getSchema();
            $this->primaryKeyConstraints[$table] = $schema->getTablePrimaryKey($table, true);
        }

        $tablePrimaryKey = $this->primaryKeyConstraints[$table];
        if ($tablePrimaryKey) {
            $primaryKey = new PrimaryKey();
            $primaryKey->setName($tablePrimaryKey->name);
            $primaryKey->setColumns($tablePrimaryKey->columnNames);
        }

        return $primaryKey;
    }

    /**
     * Returns the columns of the table.
     * @param array<IndexInterface> $indexes
     * @return array<string, ColumnInterface>
     * @throws NotSupportedException
     */
    private function getColumns(string $table, array $indexes = []): array
    {
        $mappedColumns = [];
        $tableSchema = $this->getTableSchema($table);
        if ($tableSchema === null) {
            return [];
        }
        $schema = $this->getSchemaType();
        $engineVersion = $this->getEngineVersion();

        foreach ($tableSchema->columns as $column) {
            $isUnique = $this->isUnique($indexes, $column);

            $mappedColumn = ColumnFactory::build($column->type);
            $mappedColumn->setName($column->name);
            if ($column->size === null && $column->precision === null && $column->scale === null) {
                $mappedColumn->setLength(
                    Schema::getDefaultLength($schema, $mappedColumn->getType(), $engineVersion),
                    $schema,
                    $engineVersion
                );
            } else {
                $mappedColumn->setSize($column->size);
                $mappedColumn->setPrecision($column->precision);
                $mappedColumn->setScale($column->scale);
            }
            $mappedColumn->setNotNull($column->allowNull ? null : true);
            $mappedColumn->setUnique($isUnique);
            $mappedColumn->setDefault($column->defaultValue);
            $mappedColumn->setPrimaryKey($column->isPrimaryKey);
            $mappedColumn->setAutoIncrement($column->autoIncrement);
            $mappedColumn->setUnsigned($column->unsigned);
            $mappedColumn->setComment($column->comment ?: null);

            $mappedColumns[$column->name] = $mappedColumn;
        }

        return $mappedColumns;
    }

    /**
     * @param array<IndexInterface> $indexes
     */
    private function isUnique(array $indexes, ColumnSchema $column): bool
    {
        foreach ($indexes as $index) {
            $indexColumns = $index->getColumns();
            if ($index->isUnique() && \count($indexColumns) === 1 && $indexColumns[0] === $column->name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the suppressed foreign keys that must be added in migration at the end.
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array
    {
        return $this->suppressedForeignKeys;
    }

    /**
     * Returns a table schema of the table.
     */
    public function getTableSchema(string $table): ?TableSchema
    {
        if (!\array_key_exists($table, $this->tableSchemas)) {
            $this->tableSchemas[$table] = $this->db->getTableSchema($table, true);
        }

        return $this->tableSchemas[$table];
    }

    /**
     * Returns a schema type.
     * @throws NotSupportedException
     */
    public function getSchemaType(): string
    {
        /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
        $schema = $this->db->getSchema();
        return Schema::identifySchema($schema);
    }

    /**
     * Returns a DB engine version.
     */
    public function getEngineVersion(): ?string
    {
        try {
            $slavePdo = $this->db->getSlavePdo();
            if ($slavePdo === null) {
                return null;
            }

            return $slavePdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable) {
            return null;
        }
    }
}
