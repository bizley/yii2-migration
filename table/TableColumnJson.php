<?php

namespace bizley\migration\table;

/**
 * Class TableColumnJson
 * @package bizley\migration\table
 */
class TableColumnJson extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'json()';
    }
}
