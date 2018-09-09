<?php

declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnBigUPK
 * @package bizley\migration\table
 */
class TableColumnBigUPK extends TableColumnBigPK
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table): void
    {
        parent::buildSpecificDefinition($table);
        if ($table->generalSchema) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
