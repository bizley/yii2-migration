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
use Throwable;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\cubrid\Schema as CubridSchema;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\mssql\Schema as MssqlSchema;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\oci\Schema as OciSchema;
use yii\db\pgsql\Schema as PgsqlSchema;
use yii\db\sqlite\Schema as SqliteSchema;
use yii\db\TableSchema;

final class TableMapper implements TableMapperInterface
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /** @var array<ForeignKeyInterface> */
    private $suppressedForeignKeys = [];

    /**
     * Returns a structure of the table.
     * @param string $table
     * @param array<string> $referencesToPostpone
     * @return StructureInterface
     * @throws NotSupportedException
     */
    public function getStructureOf(string $table, array $referencesToPostpone = []): StructureInterface
    {
        $this->suppressedForeignKeys = [];
        $foreignKeys = $this->getForeignKeys($table);
        /** @var ForeignKeyInterface $foreignKey */
        foreach ($foreignKeys as $foreignKeyName => $foreignKey) {
            if (in_array($foreignKey->getReferredTable(), $referencesToPostpone, true)) {
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
     * @param string $table
     * @return array<ForeignKeyInterface>
     * @throws NotSupportedException
     */
    private function getForeignKeys(string $table): array
    {
        $mappedForeignKeys = [];
        /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
        $schema = $this->db->getSchema();
        $tableForeignKeys = $schema->getTableForeignKeys($table, true);

        /** @var ForeignKeyConstraint $foreignKey */
        foreach ($tableForeignKeys as $foreignKey) {
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
     * @param string $table
     * @return array<IndexInterface>
     * @throws NotSupportedException
     */
    private function getIndexes(string $table): array
    {
        $mappedIndexes = [];
        /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
        $schema = $this->db->getSchema();
        $tableIndexes = $schema->getTableIndexes($table, true);

        /** @var IndexConstraint $index */
        foreach ($tableIndexes as $index) {
            if ($index->isPrimary === false) {
                $mappedIndex = new Index();
                $mappedIndex->setName($index->name);
                $mappedIndex->setUnique($index->isUnique);
                $mappedIndex->setColumns($index->columnNames);

                $mappedIndexes[$index->name] = $mappedIndex;
            }
        }

        return $mappedIndexes;
    }

    /**
     * Returns a primary key of the table.
     * @param string $table
     * @return PrimaryKeyInterface|null
     * @throws NotSupportedException
     */
    private function getPrimaryKey(string $table): ?PrimaryKeyInterface
    {
        $primaryKey = null;

        /** @var CubridSchema|MssqlSchema|MysqlSchema|OciSchema|PgsqlSchema|SqliteSchema $schema */
        $schema = $this->db->getSchema();
        /** @var Constraint|null $tablePrimaryKey */
        $tablePrimaryKey = $schema->getTablePrimaryKey($table, true);
        if ($tablePrimaryKey) {
            $primaryKey = new PrimaryKey();
            $primaryKey->setName($tablePrimaryKey->name);
            $primaryKey->setColumns($tablePrimaryKey->columnNames);
        }

        return $primaryKey;
    }

    /**
     * Returns the columns of the table.
     * @param string $table
     * @param array<IndexInterface> $indexes
     * @return array<string, ColumnInterface>
     */
    private function getColumns(string $table, array $indexes = []): array
    {
        $mappedColumns = [];
        $tableSchema = $this->getTableSchema($table);
        if ($tableSchema === null) {
            return [];
        }

        foreach ($tableSchema->columns as $column) {
            $isUnique = false;

            /** @var IndexInterface $index */
            foreach ($indexes as $index) {
                $indexColumns = $index->getColumns();
                if ($index->isUnique() && count($indexColumns) === 1 && $indexColumns[0] === $column->name) {
                    $isUnique = true;
                    break;
                }
            }

            $mappedColumn = ColumnFactory::build($column->type);
            $mappedColumn->setName($column->name);
            $mappedColumn->setSize($column->size);
            $mappedColumn->setPrecision($column->precision);
            $mappedColumn->setScale($column->scale);
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
     * Returns the suppressed foreign keys that must be added in migration at the end.
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array
    {
        return $this->suppressedForeignKeys;
    }

    /**
     * Returns a table schema of the table.
     * @param string $table
     * @return TableSchema|null
     */
    public function getTableSchema(string $table): ?TableSchema
    {
        return $this->db->getTableSchema($table);
    }

    /**
     * Returns a schema type.
     * @return string
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
     * @return string|null
     */
    public function getEngineVersion(): ?string
    {
        try {
            return $this->db->getSlavePdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Throwable $exception) {
            return null;
        }
    }
}
