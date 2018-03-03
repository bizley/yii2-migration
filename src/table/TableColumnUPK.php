<?php

namespace bizley\migration\table;

class TableColumnUPK extends TableColumnPK
{
    public function buildSpecificDefinition($general, $schema, $composite)
    {
        parent::buildSpecificDefinition($general, $schema, $composite);
        if ($general) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
