<?php

namespace bizley\migration\table;

class TableColumnBigInt extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        if ($general && !$composite && $this->isPrimaryKey) {
            $this->isPkPossible = false;
            if ($schema === TableStructure::SCHEMA_MSSQL) {
                $this->isNotNullPossible = false;
            }
            $this->definition[] = 'bigPrimaryKey()';
        } else {
            $this->definition[] = "bigInteger({$this->length})";
        }
    }
}
