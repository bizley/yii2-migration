<?php

namespace bizley\migration\table;

class TableColumnBigUPK extends TableColumnBigPK
{
    public function buildSpecificDefinition($schema, $general)
    {
        parent::buildSpecificDefinition($schema, $general);
        if ($general) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
