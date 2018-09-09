<?php

declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnBigPK
 * @package bizley\migration\table
 */
class TableColumnBigPK extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table): void
    {
        $this->definition[] = 'bigPrimaryKey(' . ($table->generalSchema ? null : $this->length) . ')';
        if ($table->generalSchema) {
            $this->isPkPossible = false;
            $this->isNotNullPossible = false;
        }
    }
}
