<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Schema;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;

final class SchemaTest extends TestCase
{
    /**
     * @test
     * @dataProvider providerForSchema
     * @param string $schemaClass
     * @param string $expected
     * @throws InvalidConfigException
     */
    public function shouldReturnProperSchemaType(string $schemaClass, string $expected): void
    {
        $schema = Yii::createObject(
            [
                'class' => $schemaClass,
                'db' => $this->createMock(Connection::class)
            ]
        );
        $this->assertSame($expected, Schema::identifySchema($schema));
    }

    public function providerForSchema(): array
    {
        return [
            'cubrid' => [\yii\db\cubrid\Schema::class, Schema::CUBRID],
            'mssql' => [\yii\db\mssql\Schema::class, Schema::MSSQL],
            'mysql' => [\yii\db\mysql\Schema::class, Schema::MYSQL],
            'oci' => [\yii\db\oci\Schema::class, Schema::OCI],
            'pgsql' => [\yii\db\pgsql\Schema::class, Schema::PGSQL],
            'sqlite' => [\yii\db\sqlite\Schema::class, Schema::SQLITE],
        ];
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldCheckIfSchemaIsSqlite(): void
    {
        $this->assertTrue(Schema::isSQLite(Yii::createObject(\yii\db\sqlite\Schema::class)));
        $this->assertFalse(Schema::isSQLite(new stdClass()));
    }
}
