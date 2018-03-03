<?php

namespace bizley\migration\table;

class TableColumnInt extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        if ($general && !$composite && $this->isPrimaryKey) {
            $this->isPkPossible = false;
            if ($schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
            $this->definition[] = 'primaryKey()';
        } else {
            $this->definition[] = "integer({$this->length})";
        }
    }
}
