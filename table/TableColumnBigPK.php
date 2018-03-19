<?php

namespace bizley\migration\table;

class TableColumnBigPK extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'bigPrimaryKey(' . ($table->generalSchema ? null : $this->length) . ')';
        if ($table->generalSchema) {
            $this->isPkPossible = false;
            if ($table->schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
        }
    }
}
