<?php

namespace bizley\migration\table;

class TableColumnMoney extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "money({$this->length})";
    }
}
