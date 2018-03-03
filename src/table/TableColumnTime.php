<?php

namespace bizley\migration\table;

class TableColumnTime extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "time({$this->length})";
    }
}
