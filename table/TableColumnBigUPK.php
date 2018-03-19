<?php

namespace bizley\migration\table;

class TableColumnBigUPK extends TableColumnBigPK
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        parent::buildSpecificDefinition($table);
        if ($table->generalSchema) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
