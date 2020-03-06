<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ColumnFactory;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\Index;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\Structure;
use bizley\migration\table\StructureInterface;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\TableSchema;

class TableMapper implements TableMapperInterface
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
     * @throws InvalidConfigException
     */
    public function mapTable(string $table): void
    {
        $this->setStructure($table);
    }

    public function getStructure(): StructureInterface
    {
        return $this->structure;
    }

    /**
     * @param string $table
     * @throws InvalidConfigException
     */
    private function setStructure(string $table): void
    {
        $indexes = $this->getIndexes($table);

        $this->structure = new Structure();
        $this->structure->setName($table);
        $this->structure->setPrimaryKey($this->getPrimaryKey($table));
        $this->structure->setForeignKeys($this->getForeignKeys($table));
        $this->structure->setIndexes($indexes);
        $this->structure->setColumns($this->getColumns($table, $indexes));
    }

    private function getForeignKeys(string $table): array
    {
        $mappedForeignKeys = [];
        $tableForeignKeys = $this->db->schema->getTableForeignKeys($table, true);

        /** @var $foreignKey ForeignKeyConstraint */
        foreach ($tableForeignKeys as $foreignKey) {
            $mappedForeignKeys[$foreignKey->name] = new ForeignKey([
                'name' => $foreignKey->name,
                'columns' => $foreignKey->columnNames,
                'referencedTable' => $foreignKey->foreignTableName,
                'referencedColumns' => $foreignKey->foreignColumnNames,
                'onDelete' => $foreignKey->onDelete,
                'onUpdate' => $foreignKey->onUpdate,
            ]);
        }

        return $mappedForeignKeys;
    }

    private function getIndexes(string $table): array
    {
        $mappedIndexes = [];
        $tableIndexes = $this->db->schema->getTableIndexes($table, true);

        /** @var $index IndexConstraint */
        foreach ($tableIndexes as $index) {
            if ($index->isPrimary === false) {
                $mappedIndexes[$index->name] = new Index([
                    'name' => $index->name,
                    'unique' => $index->isUnique,
                    'columns' => $index->columnNames
                ]);
            }
        }

        return $mappedIndexes;
    }

    private function getPrimaryKey(string $table): PrimaryKey
    {
        $primaryKeyData = [];

        /** @var $tablePrimaryKey Constraint */
        $tablePrimaryKey = $this->db->schema->getTablePrimaryKey($table, true);
        if ($tablePrimaryKey) {
            $primaryKeyData = [
                'columns' => $tablePrimaryKey->columnNames,
                'name' => $tablePrimaryKey->name,
            ];
        }

        return new PrimaryKey($primaryKeyData);
    }

    /**
     * @param string $table
     * @param array $indexes
     * @return array
     * @throws InvalidConfigException
     */
    private function getColumns(string $table, array $indexes = []): array
    {
        $mappedColumns = [];
        $tableSchema = $this->getSchema($table);

        foreach ($tableSchema->columns as $column) {
            $isUnique = false;

            foreach ($indexes as $index) {
                if ($index->unique && $index->columns[0] === $column->name && count($index->columns) === 1) {
                    $isUnique = true;
                    break;
                }
            }

            $mappedColumns[$column->name] = ColumnFactory::build([
                'name' => $column->name,
                'type' => $column->type,
                'size' => $column->size,
                'precision' => $column->precision,
                'scale' => $column->scale,
                'isNotNull' => $column->allowNull ? null : true,
                'isUnique' => $isUnique,
                'default' => $column->defaultValue,
                'isPrimaryKey' => $column->isPrimaryKey,
                'autoIncrement' => $column->autoIncrement,
                'isUnsigned' => $column->unsigned,
                'comment' => $column->comment ?: null,
            ]);
        }

        return $mappedColumns;
    }

    public function getSchema(string $table): TableSchema
    {
        return $this->db->getTableSchema($table);
    }
}
