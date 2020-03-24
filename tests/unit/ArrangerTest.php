<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Arranger;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;
use bizley\migration\TableMapperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_keys;

class ArrangerTest extends TestCase
{
    public function providerForArrange(): array
    {
        return [
            'no tables' => [[], [], []],
            'no references' => [['A' => [], 'B' => [], 'C' => []], ['A', 'B', 'C'], []],
            'case 1' => [['A' => ['B'], 'B' => ['C'], 'C' => []], ['C', 'B', 'A'], []],
            'case 2' => [['A' => [], 'B' => ['A', 'C'], 'C' => ['A']], ['A', 'C', 'B'], []],
            'case 3' => [
                ['A' => ['C', 'D'], 'B' => ['A', 'C'], 'C' => ['D'], 'D' => [], 'E' => []],
                ['D', 'E', 'C', 'A', 'B'],
                []
            ],
            'case 4' => [
                ['A' => [], 'B' => ['D'], 'C' => ['E'], 'D' => ['A'], 'E' => []],
                ['A', 'D', 'E', 'B', 'C'],
                []
            ],
            'case 5' => [['A' => ['B'], 'B' => ['A']], ['B', 'A'], ['A']],
            'case 6' => [['A' => ['B'], 'B' => ['C'], 'C' => ['A']], ['C', 'B', 'A'], ['A']],
            'case 7' => [['A' => ['B'], 'B' => ['A'], 'C' => ['A']], ['B', 'C', 'A'], ['A']],
            'case 8' => [
                ['A' => ['B', 'C'], 'B' => ['A', 'C'], 'C' => ['A', 'B']],
                ['C', 'B', 'A'],
                ['A', 'B'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForArrange
     * @param array $inputData
     * @param array $tablesInOrder
     * @param array $referencesToPostpone
     */
    public function shouldArrangeTables(
        array $inputData,
        array $tablesInOrder,
        array $referencesToPostpone
    ): void {
        $structure = $this->createMock(StructureInterface::class);

        $callbacks = [];
        foreach ($inputData as $tableName => $referencedTables) {
            $mockedReferencedTables = [];
            foreach ($referencedTables as $referencedTable) {
                $foreignKey = $this->createMock(ForeignKeyInterface::class);
                $foreignKey->method('getReferencedTable')->willReturn($referencedTable);
                $mockedReferencedTables[] = $foreignKey;
            }
            $callbacks[] = $mockedReferencedTables;
        }

        $structure->method('getForeignKeys')->willReturnOnConsecutiveCalls(...$callbacks);
        /** @var TableMapperInterface|MockObject $tableMapper */
        $tableMapper = $this->createMock(TableMapperInterface::class);
        $tableMapper->method('getStructureOf')->willReturn($structure);
        $arranger = new Arranger($tableMapper);

        $arranger->arrangeMigrations(array_keys($inputData));

        $this->assertSame($tablesInOrder, $arranger->getTablesInOrder());
        $this->assertSame($referencesToPostpone, $arranger->getReferencesToPostpone());
    }
}
