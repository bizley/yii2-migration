<?php

namespace bizley\migration\table;

class TableColumnDouble extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "double({$this->length})";
    }
}
