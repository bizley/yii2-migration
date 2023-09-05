<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\HistoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;
use yii\console\controllers\BaseMigrateController;
use yii\console\controllers\MigrateController;
use yii\db\Command;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\Query;
use yii\db\Schema;
use yii\db\TableSchema;

/** @group historymanager */
final class HistoryManagerTest extends TestCase
{
    /** @var MockObject&Connection */
    private $db;

    /** @var HistoryManager */
    private $manager;

    /** @var MockObject&Schema */
    private $schema;

    /** @var MockObject&Query */
    private $query;

    protected function setUp(): void
    {
        $this->schema = $this->createMock(Schema::class);
        $this->db = $this->createMock(Connection::class);
        $this->db->method('getSchema')->willReturn($this->schema);
        $this->query = $this->createMock(Query::class);
        $this->manager = new HistoryManager($this->db, $this->query, 'table');
    }

    /**
     * @test
     * @throws NotSupportedException
     * @throws Exception
     */
    public function shouldAddHistoryAndNotCreateTable(): void
    {
        $this->schema->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $command = $this->createMock(Command::class);
        $command->expects(self::once())->method('insert')->willReturn($command);
        $this->db->expects(self::once())->method('createCommand')->willReturn($command);
        $this->manager->addHistory('migration');
    }

    /**
     * @test
     * @throws NotSupportedException
     * @throws Exception
     */
    public function shouldAddHistoryAndCreateTable(): void
    {
        $this->schema->method('getTableSchema')->willReturn(null);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())->method('createTable')->with(
            'table',
            self::callback(
                static function (array $structure): bool {
                    return $structure === [
                        'version' => 'varchar(' . MigrateController::MAX_NAME_LENGTH . ') NOT NULL PRIMARY KEY',
                        'apply_time' => 'integer'
                    ];
                }
            )
        )->willReturn($command);
        $time = \time();
        $command->expects(self::exactly(2))->method('insert')->withConsecutive(
            [
                'table',
                self::callback(
                    static function (array $structure) use ($time): bool {
                        return $structure['version'] === BaseMigrateController::BASE_MIGRATION
                            && $structure['apply_time'] >= $time - 1
                            && $structure['apply_time'] <= $time + 1;
                    }
                )
            ],
            [
                'table',
                self::callback(
                    static function (array $structure) use ($time): bool {
                        return $structure['version'] === 'migration'
                            && $structure['apply_time'] >= $time - 1
                            && $structure['apply_time'] <= $time + 1;
                    }
                )
            ]
        )->willReturn($command);

        $this->db->expects(self::exactly(3))->method('createCommand')->willReturn($command);
        $this->manager->addHistory('migration');
    }

    /**
     * @test
     * @throws NotSupportedException
     * @throws Exception
     */
    public function shouldAddHistoryWithNamespace(): void
    {
        $this->schema->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $command = $this->createMock(Command::class);
        $time = \time();
        $command->expects(self::once())->method('insert')->with(
            'table',
            self::callback(
                static function (array $structure) use ($time): bool {
                    return $structure['version'] === 'a\\b\\migration'
                        && $structure['apply_time'] >= $time - 1
                        && $structure['apply_time'] <= $time + 1;
                }
            )
        )->willReturn($command);
        $this->db->expects(self::once())->method('createCommand')->willReturn($command);
        $this->manager->addHistory('migration', 'a\\b');
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnEmptyHistoryArrayWhenNoTableHistory(): void
    {
        $this->schema->method('getTableSchema')->willReturn(null);
        self::assertSame([], $this->manager->fetchHistory());
    }

    public function providerForHistory(): array
    {
        return [
            'only base' => [
                [['version' => BaseMigrateController::BASE_MIGRATION, 'apply_time' => 1]],
                []
            ],
            [
                [
                    ['version' => BaseMigrateController::BASE_MIGRATION, 'apply_time' => 1],
                    ['version' => 'a', 'apply_time' => 1],
                    ['version' => 'b', 'apply_time' => '2'],
                    ['version' => 'c', 'apply_time' => 3],
                ],
                [
                    'c' => 3,
                    'b' => 2,
                    'a' => 1,
                ]
            ],
            [
                [
                    ['version' => 'a', 'apply_time' => 1],
                    ['version' => 'b', 'apply_time' => 1],
                    ['version' => 'c', 'apply_time' => 1],
                ],
                [
                    'c' => 1,
                    'b' => 1,
                    'a' => 1,
                ]
            ],
            [
                [
                    ['version' => 'm200328_135959_update_table_a', 'apply_time' => 1],
                    ['version' => 'm200328_140000_update_table_a', 'apply_time' => 1],
                    ['version' => 'M200328_140001_UPDATE_TABLE_A', 'apply_time' => 1],
                ],
                [
                    'M200328_140001_UPDATE_TABLE_A' => 1,
                    'm200328_140000_update_table_a' => 1,
                    'm200328_135959_update_table_a' => 1,
                ]
            ],
            [
                [
                    ['version' => 'm200328_140001_update_table_a', 'apply_time' => 1],
                    ['version' => 'm200328_140001_update_table_b', 'apply_time' => 1],
                    ['version' => 'm200328_140001_update_table_c', 'apply_time' => 1],
                ],
                [
                    'm200328_140001_update_table_c' => 1,
                    'm200328_140001_update_table_b' => 1,
                    'm200328_140001_update_table_a' => 1,
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider providerForHistory
     * @param array $rows
     * @param array $expected
     * @throws NotSupportedException
     */
    public function shouldReturnHistoryInProperOrder(array $rows, array $expected): void
    {
        $this->schema->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $this->query->method('select')->with(['version', 'apply_time'])->willReturnSelf();
        $this->query->method('from')->with('table')->willReturnSelf();
        $this->query
            ->method('orderBy')
            ->with(['apply_time' => SORT_DESC, 'version' => SORT_DESC])
            ->willReturnSelf();
        $this->query->method('all')->with($this->db)->willReturn($rows);
        self::assertSame($expected, $this->manager->fetchHistory());
    }
}
