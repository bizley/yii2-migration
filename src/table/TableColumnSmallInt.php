<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnSmallInt
 * @package bizley\migration\table
 */
class TableColumnSmallInt extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'smallInteger(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
