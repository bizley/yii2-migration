<?php

namespace bizley\migration\table;

class TableColumnBinary extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "binary({$this->length})";
    }
}
