<?php

namespace bizley\migration\table;

class TableColumnBigPK extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "bigPrimaryKey({$this->length})";
        if ($general) {
            $this->isPkPossible = false;
            if ($schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
        }
    }
}
