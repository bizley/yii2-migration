<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\helpers\Json;

use function is_array;

class JsonColumn extends Column
{
    /**
     * Checks if default value is JSONed array. If so it's decoded.
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
            } catch (InvalidArgumentException $exception) {
            }
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    protected function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'json()';
    }
}
