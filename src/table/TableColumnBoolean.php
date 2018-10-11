<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnBoolean
 * @package bizley\migration\table
 */
class TableColumnBoolean extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'boolean()';
    }
}
