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

    /** @test */
    public function shouldRenderProperTemplateForUp(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([]);
        $index->method('getName')->willReturn('pk');
        $index->method('isUnique')->willReturn(false);

        $this->renderer->setCreateIndexTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->renderUp($index, 'test'));
    }

    /** @test */
    public function shouldRenderProperTemplateForDown(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('pk');

        $this->renderer->setDropIndexTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->renderDown($index, 'test'));
    }

    public function providerForRender(): array
    {
        return [
            '1col 0ind non-unique' => [
                0,
                ['aaa'],
                false,
                '$this->createIndex(\'idx\', \'test\', [\'aaa\']);',
                '$this->dropIndex(\'idx\', \'test\');',
            ],
            '1col 4ind unique' => [
                4,
                ['bbb'],
                true,
                '    $this->createIndex(\'idx\', \'test\', [\'bbb\'], true);',
                '    $this->dropIndex(\'idx\', \'test\');',
            ],
            '2col 0ind non-unique' => [
                0,
                ['ccc', 'ddd'],
                false,
                '$this->createIndex(\'idx\', \'test\', [\'ccc\', \'ddd\']);',
                '$this->dropIndex(\'idx\', \'test\');',
            ],
            '2col 3ind unique' => [
                3,
                ['ccc', 'ddd'],
                true,
                '   $this->createIndex(\'idx\', \'test\', [\'ccc\', \'ddd\'], true);',
                '   $this->dropIndex(\'idx\', \'test\');',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param array $columns
     * @param bool $unique
     * @param string $expectedCreate
     * @param string $expectedDrop
     */
    public function shouldRenderProperlyPrimaryKey(
        int $indent,
        array $columns,
        bool $unique,
        string $expectedCreate,
        string $expectedDrop
    ): void {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn($columns);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn($unique);

        $this->assertSame($expectedCreate, $this->renderer->renderUp($index, 'test', $indent));
        $this->assertSame($expectedDrop, $this->renderer->renderDown($index, 'test', $indent));
    }
}
