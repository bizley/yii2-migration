<?php

namespace bizley\migration\table;

class TableColumnString extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "string({$this->length})";
    }
}
