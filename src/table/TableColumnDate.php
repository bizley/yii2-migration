<?php

namespace bizley\migration\table;

class TableColumnDate extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = 'date()';
    }
}
