<?php

namespace bizley\migration\table;

class TableColumnDecimal extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "decimal({$this->length})";
    }
}
