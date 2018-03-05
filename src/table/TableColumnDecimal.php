<?php

namespace bizley\migration\table;

class TableColumnDecimal extends TableColumn
{
    /**
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision . ($this->scale ? ', ' . $this->scale : null);
    }

    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'decimal(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
