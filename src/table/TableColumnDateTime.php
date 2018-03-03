<?php

namespace bizley\migration\table;

class TableColumnDateTime extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = "dateTime({$this->length})";
    }
}
