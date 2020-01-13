<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidConfigException;
use yii\db\Schema;

use function array_key_exists;

class ColumnFactory
{
    /**
     * Builds table column object based on the type.
     * @param array $configuration
     * @return Column
     * @throws InvalidConfigException
     */
    public static function build(array $configuration = []): ?Column
    {
        if (!array_key_exists('type', $configuration)) {
            throw new InvalidConfigException('Configuration for ColumnFactory is missing "type" key.');
        }

        switch ($configuration['type']) {
            case Schema::TYPE_PK:
                return new PrimaryKeyColumn($configuration);

            case Schema::TYPE_UPK:
                return new UnsignedPrimaryKeyColumn($configuration);

            case Schema::TYPE_BIGPK:
                return new BigPrimaryKeyColumn($configuration);

            case Schema::TYPE_UBIGPK:
                return new BigUnsignedPrimaryKeyColumn($configuration);

            case Schema::TYPE_CHAR:
                return new CharacterColumn($configuration);

            case Schema::TYPE_STRING:
                return new StringColumn($configuration);

            case Schema::TYPE_TEXT:
                return new TextColumn($configuration);

            case Schema::TYPE_TINYINT:
                return new TinyIntegerColumn($configuration);

            case Schema::TYPE_SMALLINT:
                return new SmallIntegerColumn($configuration);

            case Schema::TYPE_INTEGER:
                return new IntegerColumn($configuration);

            case Schema::TYPE_BIGINT:
                return new BigIntegerColumn($configuration);

            case Schema::TYPE_BINARY:
                return new BinaryColumn($configuration);

            case Schema::TYPE_FLOAT:
                return new FloatColumn($configuration);

            case Schema::TYPE_DOUBLE:
                return new DoubleColumn($configuration);

            case Schema::TYPE_DATETIME:
                return new DateTimeColumn($configuration);

            case Schema::TYPE_TIMESTAMP:
                return new TimestampColumn($configuration);

            case Schema::TYPE_TIME:
                return new TimeColumn($configuration);

            case Schema::TYPE_DATE:
                return new DateColumn($configuration);

            case Schema::TYPE_DECIMAL:
                return new DecimalColumn($configuration);

            case Schema::TYPE_BOOLEAN:
                return new BooleanColumn($configuration);

            case Schema::TYPE_MONEY:
                return new MoneyColumn($configuration);

            case Schema::TYPE_JSON:
                return new JsonColumn($configuration);

            default:
                throw new InvalidConfigException(
                    "Unsupported schema type '{$configuration['type']}' for ColumnFactory."
                );
        }
    }
}
