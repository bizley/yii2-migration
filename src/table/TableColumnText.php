<?php

namespace bizley\migration\table;

class TableColumnText extends TableColumn
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        $this->definition[] = "text({$this->length})";
    }
}
