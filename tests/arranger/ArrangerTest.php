<?php

namespace bizley\tests\arranger;

use bizley\migration\Arranger;
use PHPUnit\Framework\TestCase;
use yii\db\Connection;

class ArrangerTest extends TestCase
{
    /**
     * @return array
     */
    public function inputProvider()
    {
        return [
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
    }

    /**
     * @dataProvider inputProvider
     * @param array $input
     * @param array $output
     */
    public function testArranger(array $input, array $output)
    {
        $arranger = new Arranger(['db' => new Connection()]);

        $this->assertSame($output, $arranger->arrangeTables($input));
    }
}
