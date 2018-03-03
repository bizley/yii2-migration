<?php

namespace bizley\migration\table;

class TableColumnFloat extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "float({$this->length})";
    }
}
