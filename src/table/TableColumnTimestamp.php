<?php

namespace bizley\migration\table;

class TableColumnTimestamp extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "timestamp({$this->length})";
    }
}
