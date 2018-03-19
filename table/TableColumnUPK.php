<?php

namespace bizley\migration\table;

class TableColumnUPK extends TableColumnPK
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
