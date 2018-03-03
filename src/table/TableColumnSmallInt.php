<?php

namespace bizley\migration\table;

class TableColumnSmallInt extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "smallInteger({$this->length})";
    }
}
