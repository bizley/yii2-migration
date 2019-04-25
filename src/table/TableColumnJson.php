<?php

namespace bizley\migration\table;

use yii\base\InvalidParamException;
use yii\helpers\Json;

/**
 * Class TableColumnJson
 * @package bizley\migration\table
 */
class TableColumnJson extends TableColumn
{
    /**
     * Checks if default value is JSONed array. If so it's decoded.
     * @since 2.6.0
     */
    public function init()
    {
        parent::init();

        if ($this->default !== '' && $this->default !== null && !is_array($this->default)) {
            try {
                $default = Json::decode($this->default);

                if (is_array($default)) {
                    $this->default = $default;
                }
            } catch (InvalidParamException $exception) {}
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'json()';
    }
}
