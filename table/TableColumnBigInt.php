<?php

namespace bizley\migration\table;

class TableColumnBigInt extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        if ($table->generalSchema && !$table->primaryKey->isComposite() && $this->isColumnInPK($table->primaryKey)) {
            $this->isPkPossible = false;
            if ($table->schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
            $this->definition[] = 'bigPrimaryKey()';
        } else {
            $this->definition[] = 'bigInteger(' . ($table->generalSchema ? null : $this->length) . ')';
        }
    }
}
