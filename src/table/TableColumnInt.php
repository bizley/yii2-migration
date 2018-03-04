<?php

namespace bizley\migration\table;

class TableColumnInt extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        if ($table->generalSchema && !$table->primaryKey->isComposite() && $this->isColumnPK($table->primaryKey)) {
            $this->isPkPossible = false;
            if ($table->schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
            $this->definition[] = 'primaryKey()';
        } else {
            $this->definition[] = "integer({$this->length})";
        }
    }
}
