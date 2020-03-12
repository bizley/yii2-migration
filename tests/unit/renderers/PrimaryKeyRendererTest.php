<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\PrimaryKeyRenderer;
use bizley\migration\table\PrimaryKeyInterface;
use PHPUnit\Framework\TestCase;

class PrimaryKeyRendererTest extends TestCase
{
    /** @var PrimaryKeyRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new PrimaryKeyRenderer();
    }

    /**
     * @test
     */
    public function shouldReturnNullWhenNoPrimaryKey(): void
    {
        $this->assertNull($this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldReturnNullWhenPrimaryKeyIsNotComposite(): void
    {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertNull($this->renderer->render('test'));
    }

    public function providerForRender(): array
    {
        return [
            '1col 0ind' => [0, ['aaa'], '$this->addPrimaryKey(\'pk\', \'test\', [\'aaa\']);'],
            '1col 5ind' => [5, ['bbb'], '     $this->addPrimaryKey(\'pk\', \'test\', [\'bbb\']);'],
            '2col 0ind' => [0, ['ccc', 'ddd'], '$this->addPrimaryKey(\'pk\', \'test\', [\'ccc\', \'ddd\']);'],
            '3col 4ind' => [
                4,
                ['eee', 'fff', 'ggg'],
                '    $this->addPrimaryKey(\'pk\', \'test\', [\'eee\', \'fff\', \'ggg\']);',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param array $columns
     * @param string $expected
     */
    public function shouldRenderProperlyPrimaryKey(int $indent, array $columns, string $expected): void
    {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);
        $primaryKey->method('getColumns')->willReturn($columns);
        $primaryKey->method('getName')->willReturn('pk');

        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame($expected, $this->renderer->render('test', $indent));
    }
}
