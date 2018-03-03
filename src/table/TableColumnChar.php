<?php

namespace bizley\migration\table;

class TableColumnChar extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "char({$this->length})";
    }
}
