<?php

namespace bizley\migration\table;

use yii\base\InvalidConfigException;
use yii\db\Schema;

class TableColumnFactory
{
    /**
     * @param array $configuration
     * @return TableColumn
     * @throws InvalidConfigException
     */
    public static function build($configuration = [])
    {
        if (!array_key_exists('type', $configuration)) {
            throw new InvalidConfigException('Configuration for TableColumnFactory is missing "type" key.');
        }
        switch ($configuration['type']) {
            case Schema::TYPE_PK:
                return new TableColumnPK($configuration);
            case Schema::TYPE_UPK:
                return new TableColumnUPK($configuration);
            case Schema::TYPE_BIGPK:
                return new TableColumnBigPK($configuration);
            case Schema::TYPE_UBIGPK:
                return new TableColumnBigUPK($configuration);
            case Schema::TYPE_CHAR:
                return new TableColumnChar($configuration);
            case Schema::TYPE_STRING:
                return new TableColumnString($configuration);
            case Schema::TYPE_TEXT:
                return new TableColumnText($configuration);
            case Schema::TYPE_SMALLINT:
                return new TableColumnSmallInt($configuration);
            case Schema::TYPE_INTEGER:
                return new TableColumnInt($configuration);
            case Schema::TYPE_BIGINT:
                return new TableColumnBigInt($configuration);
            case Schema::TYPE_BINARY:
                return new TableColumnBinary($configuration);
            case Schema::TYPE_FLOAT:
                return new TableColumnFloat($configuration);
            case Schema::TYPE_DOUBLE:
                return new TableColumnDouble($configuration);
            case Schema::TYPE_DATETIME:
                return new TableColumnDateTime($configuration);
            case Schema::TYPE_TIMESTAMP:
                return new TableColumnTimestamp($configuration);
            case Schema::TYPE_TIME:
                return new TableColumnTime($configuration);
            case Schema::TYPE_DATE:
                return new TableColumnDate($configuration);
            case Schema::TYPE_DECIMAL:
                return new TableColumnDecimal($configuration);
            case Schema::TYPE_BOOLEAN:
                return new TableColumnBoolean($configuration);
            case Schema::TYPE_MONEY:
                return new TableColumnMoney($configuration);
            default:
                throw new InvalidConfigException("Unsupported schema type '{$configuration['type']}' for TableColumnFactory.");
        }
    }
}
