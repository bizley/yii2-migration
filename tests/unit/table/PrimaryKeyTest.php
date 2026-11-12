<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\PrimaryKey;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group primarykey
 */
final class PrimaryKeyTest extends TestCase
{
    /** @var PrimaryKey */
    private $pk;

    protected function setUp(): void
    {
        $this->pk = new PrimaryKey();
    }

    /**
     * @return array<string, array{list<string>, bool}>
     */
    public function providerForComposite(): array
    {
        return [
            'no columns' => [[], false],
            '1 column' => [['a'], false],
            '2 columns' => [['a', 'b'], true],
        ];
    }

    /**
     * @test
     * @dataProvider providerForComposite
     * @param list<string> $columns
     */
    public function shouldProperlyCheckIfKeyIsComposite(array $columns, bool $expected): void
    {
        $this->pk->setColumns($columns);
        self::assertSame($expected, $this->pk->isComposite());
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public function providerForColumns(): array
    {
        return [
            'new column' => ['new', ['old', 'new']],
            'same column' => ['old', ['old']],
        ];
    }

    /**
     * @test
     * @dataProvider providerForColumns
     * @param list<string> $expectedColumns
     */
    public function shouldProperlyAddColumn(string $newColumn, array $expectedColumns): void
    {
        $this->pk->setColumns(['old']);
        $this->pk->addColumn($newColumn);
        self::assertSame($expectedColumns, $this->pk->getColumns());
    }
}
