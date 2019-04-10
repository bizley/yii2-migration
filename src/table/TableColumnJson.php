<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use function is_array;

/**
 * Class TableColumnJson
 * @package bizley\migration\table
 */
class TableColumnJson extends TableColumn
{
    /**
     * Checks if default value is JSONed array. If so it's decoded.
     * @since 3.2.1
     */
    public function init(): void
    {
        parent::init();

        if ($this->default !== '' && $this->default !== null && !is_array($this->default)) {
            try {
                $default = Json::decode($this->default);

                if (is_array($default)) {
                    $this->default = $default;
                }
            } catch (InvalidArgumentException $exception) {}
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'json()';
    }
}
