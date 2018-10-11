<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnBinary
 * @package bizley\migration\table
 */
class TableColumnBinary extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'binary(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
