<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\IndexRenderer;
use bizley\migration\table\IndexInterface;
use PHPUnit\Framework\TestCase;

class IndexRendererTest extends TestCase
{
    /** @var IndexRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new IndexRenderer();
    }

    /**
     * @test
     */
    public function shouldReturnNullWhenNoIndex(): void
    {
        $this->assertNull($this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperTemplate(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([]);
        $index->method('getName')->willReturn('pk');
        $index->method('isUnique')->willReturn(false);

        $this->renderer->setIndex($index);
        $this->renderer->setCreateIndexTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->render('test'));
    }

    public function providerForRender(): array
    {
        return [
            '1col 0ind non-unique' => [0, ['aaa'], false, '$this->createIndex(\'idx\', \'test\', [\'aaa\']);'],
            '1col 4ind unique' => [4, ['bbb'], true, '    $this->createIndex(\'idx\', \'test\', [\'bbb\'], true);'],
            '2col 0ind non-unique' => [
                0,
                ['ccc', 'ddd'],
                false,
                '$this->createIndex(\'idx\', \'test\', [\'ccc\', \'ddd\']);',
            ],
            '2col 3ind unique' => [
                3,
                ['ccc', 'ddd'],
                true,
                '   $this->createIndex(\'idx\', \'test\', [\'ccc\', \'ddd\'], true);',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param array $columns
     * @param bool $unique
     * @param string $expected
     */
    public function shouldRenderProperlyPrimaryKey(int $indent, array $columns, bool $unique, string $expected): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn($columns);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn($unique);

        $this->renderer->setIndex($index);
        $this->assertSame($expected, $this->renderer->render('test', $indent));
    }
}
