<?php

namespace bizley\migration\table;

use yii\base\Object;

class TableStructure extends Object
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var TablePrimaryKey
     */
    public $primaryKey;
    /**
     * @var TableColumn[]
     */
    public $columns = [];
    /**
     * @var TableIndex[]
     */
    public $indexes = [];
    /**
     * @var TableForeignKey[]
     */
    public $foreignKeys = [];
    /**
     * @var string
     */
    public $schema;

    const SCHEMA_MSSQL = 'mssql';
    const SCHEMA_OCI = 'oci';
    const SCHEMA_PGSQL = 'pgsql';
    const SCHEMA_SQLITE = 'sqlite';
    const SCHEMA_CUBRID = 'cubrid';
    const SCHEMA_MYSQL = 'mysql';
    const SCHEMA_UNSUPPORTED = 'unsupported';
}
