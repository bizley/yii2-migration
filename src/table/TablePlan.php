<?php

namespace bizley\migration\table;

use yii\base\Object;

/**
 * Class TablePlan
 * @package bizley\migration\table
 *
 */
class TablePlan extends Object
{
    /**
     * @var array
     */
    public $dropColumn = [];

    /**
     * @var array
     */
    public $addColumn = [];

    /**
     * @var array
     */
    public $alterColumn = [];

    /**
     * @var array
     */
    public $dropForeignKey = [];

    /**
     * @var array
     */
    public $addForeignKey = [];

    /**
     * @var bool
     */
    public $dropPrimaryKey = false;

    /**
     * @var TablePrimaryKey
     */
    public $addPrimaryKey;

    /**
     * @var array
     */
    public $dropIndex = [];

    /**
     * @var array
     */
    public $createIndex = [];

    /**
     * Prepares updates definitions.
     * @return array
     */
    public function prepareUpdates()
    {
        $updates = [];
        /* @var $data array */
        foreach ($this->_modifications as $method => $data) {
            switch ($method) {
                case 'dropColumn':
                    foreach ($data as $column) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}'"];
                    }
                    break;
                case 'addColumn':
                    foreach ($data as $column => $type) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}', " . $this->renderColumnStructure($type)];
                    }
                    break;
                case 'alterColumn':
                    /* @var $typesList array */
                    foreach ($data as $column => $type) {
                        $updates[] = [$method, "'" . $this->generateTableName($this->tableName) . "', '{$column}', " . $this->renderColumnStructure($type)];
                    }
                    break;
                case 'addForeignKey':
                    foreach ($data as $fk => $params) {
                        $definition = [
                            "'{$fk}'",
                            "'" . $this->generateTableName($this->tableName) . "'",
                            is_array($params[0]) ? '[' . implode(', ', $params[0]) . ']' : "'{$params[0]}'",
                            "'" . $this->generateTableName($params[1]) . "'",
                            is_array($params[2]) ? '[' . implode(', ', $params[2]) . ']' : "'{$params[2]}'",
                        ];
                        if ($params[3] !== null || $params[4] !== null) {
                            $definition[] = $params[3] !== null ? "'{$params[3]}'" : 'null';
                        }
                        if ($params[4] !== null) {
                            $definition[] = "'{$params[4]}'";
                        }
                        $updates[] = [$method, implode(', ', $definition)];
                    }
                    break;
                case 'dropForeignKey':
                    foreach ($data as $fk) {
                        $updates[] = [$method, "'{$fk}', '" . $this->generateTableName($this->tableName) . "'"];
                    }
                    break;
                case 'createIndex':
                    foreach ($data as $uidx => $columns) {
                        $updates[] = [$method, "'{$uidx}', '" . $this->generateTableName($this->tableName) . "', "
                            . (count($columns) === 1 ? "'{$columns[0]}'" : "['" . implode("', '", $columns) . "']") . ', true'];
                    }
                    break;
                case 'dropIndex':
                    foreach ($data as $uidx) {
                        $updates[] = [$method, "'{$uidx}', '" . $this->generateTableName($this->tableName) . "'"];
                    }
                    break;
                case 'dropPrimaryKey':
                    $updates[] = [$method, "'primary_key', '" . $this->generateTableName($this->tableName) . "'"];
                    break;
                case 'addPrimaryKey':
                    $updates[] = [$method, "'primary_key', '" . $this->generateTableName($this->tableName) . "', "
                        . (count($data) === 1 ? "'{$data[0]}'" : "['" . implode("', '", $data) . "']")];
            }
        }
        return $updates;
    }
}
