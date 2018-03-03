<?php

namespace bizley\migration\table;

class TableColumnPK extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "primaryKey({$this->length})";
        if ($general) {
            $this->isPkPossible = false;
            if ($schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
        }
    }
}
