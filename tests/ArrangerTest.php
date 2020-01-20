<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\Arranger;
use bizley\migration\Generator;
use bizley\migration\table\Structure;
use PHPUnit\Framework\MockObject\MockObject;
use yii\base\InvalidConfigException;
use yii\db\Connection;

use function array_keys;

class ArrangerTest extends TestCase
{
    /**
     * @test
     */
    public function shouldThrowInvalidConfigExceptionWhenNoConnectionIsPassed(): void
    {
        $this->expectException(InvalidConfigException::class);

        new Arranger();
    }

    public function dataForArranger(): array
    {
        return [
            'empty' => [[], [], []],
            'no-links' => [
                [
                    'a' => [],
                    'b' => [],
                ],
                ['a', 'b'],
                []
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataForArranger
     */
    public function shouldArrangeTables(array $inputTables, array $tablesInOrder, array $suppressedForeignKeys): void
    {
        /** @var Arranger|MockObject $arranger */
        $arranger = $this->createYiiMock(Arranger::class, ['db' => $this->createMock(Connection::class)]);
        $arranger->method('getGenerator')
            ->willReturnCallback($this->callback(static function ($arg) use ($inputTables) {
                $structure = new Structure();
                $structure->foreignKeys = $inputTables[$arg];

                $generator = $this->createMock(Generator::class);
                $generator->method('getTableStructure')->willReturn($structure);
                $generator->method('getTableForeignKeys')->willReturn([]);

                return $generator;
            }));

        $arranger->arrangeMigrations(array_keys($inputTables));

        $this->assertSame($tablesInOrder, $arranger->getTablesInOrder());
        $this->assertSame($suppressedForeignKeys, $arranger->getSuppressedForeignKeys());
    }
}
