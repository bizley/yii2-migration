<?php

namespace bizley\migration\table;

class TableColumnPK extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "primaryKey({$this->length})";
        if ($table->generalSchema) {
            $this->isPkPossible = false;
            if ($table->schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
        }
    }
}
