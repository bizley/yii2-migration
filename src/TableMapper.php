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
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\TableSchema;

final class TableMapper implements TableMapperInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var StructureInterface
     */
    private $structure;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $table
     * @return StructureInterface
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function getStructureOf(string $table): StructureInterface
    {
        $indexes = $this->getIndexes($table);

        $this->structure = new Structure();
        $this->structure->setName($table);
        $this->structure->setPrimaryKey($this->getPrimaryKey($table));
        $this->structure->setForeignKeys($this->getForeignKeys($table));
        $this->structure->setIndexes($indexes);
        $this->structure->setColumns($this->getColumns($table, $indexes));

        return $this->structure;
    }

    /**
     * @param string $table
     * @return array<ForeignKeyInterface>
     * @throws NotSupportedException
     */
    private function getForeignKeys(string $table): array
    {
        $mappedForeignKeys = [];
        $tableForeignKeys = $this->db->getSchema()->getTableForeignKeys($table, true);

        /** @var $foreignKey ForeignKeyConstraint */
        foreach ($tableForeignKeys as $foreignKey) {
            $mappedForeignKey = new ForeignKey();
            $mappedForeignKey->setName($foreignKey->name);
            $mappedForeignKey->setColumns($foreignKey->columnNames);
            $mappedForeignKey->setReferencedTable($foreignKey->foreignTableName);
            $mappedForeignKey->setReferencedColumns($foreignKey->foreignColumnNames);
            $mappedForeignKey->setOnDelete($foreignKey->onDelete);
            $mappedForeignKey->setOnUpdate($foreignKey->onUpdate);

            $mappedForeignKeys[$foreignKey->name] = $mappedForeignKey;
        }

        return $mappedForeignKeys;
    }

    /**
     * @param string $table
     * @return array<IndexInterface>
     * @throws NotSupportedException
     */
    private function getIndexes(string $table): array
    {
        $mappedIndexes = [];
        $tableIndexes = $this->db->getSchema()->getTableIndexes($table, true);

        /** @var $index IndexConstraint */
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
     * @param string $table
     * @return PrimaryKeyInterface|null
     * @throws NotSupportedException
     */
    private function getPrimaryKey(string $table): ?PrimaryKeyInterface
    {
        $primaryKey = null;

        /** @var $tablePrimaryKey Constraint */
        $tablePrimaryKey = $this->db->getSchema()->getTablePrimaryKey($table, true);
        if ($tablePrimaryKey) {
            $primaryKey = new PrimaryKey();
            $primaryKey->setName($tablePrimaryKey->name);
            $primaryKey->setColumns($tablePrimaryKey->columnNames);
        }

        return $primaryKey;
    }

    /**
     * @param string $table
     * @param array<IndexInterface> $indexes
     * @return array<ColumnInterface>
     * @throws InvalidConfigException
     */
    private function getColumns(string $table, array $indexes = []): array
    {
        $mappedColumns = [];
        $tableSchema = $this->getSchema($table);

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

    public function getSchema(string $table): ?TableSchema
    {
        return $this->db->getTableSchema($table);
    }
}
