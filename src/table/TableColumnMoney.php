<?php

namespace bizley\migration\table;

class TableColumnMoney extends TableColumn
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
        $this->definition[] = 'money(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
