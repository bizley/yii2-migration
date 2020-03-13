<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class UpdateInstructions
{
    /**
     * @var array
     */
    private $columnsToDrop = [];

    /**
     * @var array
     */
    private $columnsToAdd = [];

    /**
     * @var array
     */
    private $columnToAlter = [];

    /**
     * @var array
     */
    private $foreignKeysToDrop = [];

    /**
     * @var array
     */
    private $foreignKeysToAdd = [];

    /**
     * @var string
     */
    private $primaryKeyToDrop;

    /**
     * @var PrimaryKey
     */
    private $primaryKeyToAdd;

    /**
     * @var array
     */
    private $indexesToDrop = [];

    /**
     * @var array
     */
    private $indexToAdd = [];
}
