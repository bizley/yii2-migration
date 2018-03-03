<?php

namespace bizley\migration\table;

class TableColumnBoolean extends TableColumn
{
    public function buildSpecificDefinition($schema, $general)
    {
        $this->definition[] = 'boolean()';
    }
}
