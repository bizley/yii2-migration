<?php

declare(strict_types=1);

namespace bizley\tests;

use bizley\migration\Arranger;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;
use bizley\migration\TableMapperInterface;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\db\Connection;

class ArrangerTest extends TestCase
{
    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenConnectionNotSet(): void
    {
        $this->expectException(InvalidConfigException::class);
        (new Arranger())->arrangeMigrations([]);
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenMapperNotSet(): void
    {
        $this->expectException(InvalidConfigException::class);
        (new Arranger(null, $this->createMock(Connection::class)))->arrangeMigrations([]);
    }

    public function providerForArrange(): array
    {
        return [
            'no tables' => [[], [], []],
            'no references' => [['A', 'B'], ['A', 'B'], []],
        ];

        /*
[
            [
                [
                    'A' => ['B'],
                    'B' => ['C'],
                    'C' => [],
                ],
                [
                    'order' => ['C', 'B', 'A'],
                    'suppressForeignKeys' => [],
                ]
            ],
            [
                [
                    'A' => [],
                    'B' => ['A', 'C'],
                    'C' => ['A'],
                ],
                [
                    'order' => ['A', 'C', 'B'],
                    'suppressForeignKeys' => [],
                ]
            ],
            [
                [
                    'A' => ['C', 'D'],
                    'B' => ['A', 'C'],
                    'C' => ['D'],
                    'D' => [],
                    'E' => [],
                ],
                [
                    'order' => ['D', 'E', 'C', 'A', 'B'],
                    'suppressForeignKeys' => [],
                ]
            ],
            [
                [
                    'A' => [],
                    'B' => ['D'],
                    'C' => ['E'],
                    'D' => ['A'],
                    'E' => [],
                ],
                [
                    'order' => ['A', 'D', 'E', 'B', 'C'],
                    'suppressForeignKeys' => [],
                ]
            ],
            [
                [
                    'A' => ['B'],
                    'B' => ['A'],
                ],
                [
                    'order' => ['B', 'A'],
                    'suppressForeignKeys' => ['B' => ['A']],
                ]
            ],
            [
                [
                    'A' => ['B'],
                    'B' => ['C'],
                    'C' => ['A'],
                ],
                [
                    'order' => ['C', 'B', 'A'],
                    'suppressForeignKeys' => ['C' => ['A']],
                ]
            ],
            [
                [
                    'A' => ['B'],
                    'B' => ['A'],
                    'C' => ['A'],
                ],
                [
                    'order' => ['B', 'C', 'A'],
                    'suppressForeignKeys' => [
                        'C' => ['A'],
                        'B' => ['A'],
                    ],
                ]
            ],
            [
                [
                    'A' => ['B', 'C'],
                    'B' => ['A', 'C'],
                    'C' => ['A', 'B'],
                ],
                [
                    'order' => ['C', 'B', 'A'],
                    'suppressForeignKeys' => [
                        'C' => ['A', 'B'],
                        'B' => ['A'],
                    ],
                ]
            ],
        ];
         */
    }

    /**
     * @test
     * @dataProvider providerForArrange
     * @param array $inputTables
     * @param array $tablesInOrder
     * @param array $suppressedForeignKeys
     * @throws InvalidConfigException
     */
    public function shouldArrangeTables(
        array $inputTables,
        array $tablesInOrder,
        array $suppressedForeignKeys
    ): void {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getForeignKeys')->willReturn($this->callback(function () {
            $foreignKey = $this->createMock(ForeignKeyInterface::class);
            $foreignKey->method('getReferencedTable')->willReturn();
            return $foreignKey;
        }));
        $tableMapper = $this->createMock(TableMapperInterface::class);
        $tableMapper->method('getStructure')->willReturn($structure);
        $arranger = new Arranger($tableMapper, $this->createMock(Connection::class));

        $arranger->arrangeMigrations($inputTables);

        $this->assertSame($tablesInOrder, $arranger->getTablesInOrder());
        $this->assertSame($suppressedForeignKeys, $arranger->getSuppressedForeignKeys());
    }
}
