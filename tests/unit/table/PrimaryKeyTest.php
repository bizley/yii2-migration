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
     * @param array $columns
     * @param bool $expected
     */
    public function shouldProperlyCheckIfKeyIsComposite(array $columns, bool $expected): void
    {
        $this->pk->setColumns($columns);
        $this->assertSame($expected, $this->pk->isComposite());
    }

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
     * @param string $newColumn
     * @param array $expectedColumns
     */
    public function shouldProperlyAddColumn(string $newColumn, array $expectedColumns): void
    {
        $this->pk->setColumns(['old']);
        $this->pk->addColumn($newColumn);
        $this->assertSame($expectedColumns, $this->pk->getColumns());
    }
}
