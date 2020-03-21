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

    /** @test */
    public function shouldReturnNullWhenNoPrimaryKey(): void
    {
        $this->assertNull($this->renderer->renderUp(null, 'test'));
        $this->assertNull($this->renderer->renderDown(null, 'test'));
    }

    /** @test */
    public function shouldReturnNullWhenPrimaryKeyIsNotComposite(): void
    {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertNull($this->renderer->renderUp($primaryKey, 'test'));
        $this->assertNull($this->renderer->renderDown($primaryKey, 'test'));
    }

    /** @test */
    public function shouldRenderProperTemplateForUp(): void
    {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);
        $primaryKey->method('getColumns')->willReturn([]);
        $primaryKey->method('getName')->willReturn('pk');

        $this->renderer->setAddKeyTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->renderUp($primaryKey, 'test'));
    }

    /** @test */
    public function shouldRenderProperTemplateForDown(): void
    {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);
        $primaryKey->method('getName')->willReturn('pk');

        $this->renderer->setDropKeyTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->renderDown($primaryKey, 'test'));
    }

    public function providerForRender(): array
    {
        return [
            '1col 0ind' => [
                0,
                ['aaa'],
                '$this->addPrimaryKey(\'pk\', \'test\', [\'aaa\']);',
                '$this->dropPrimaryKey(\'pk\', \'test\');',
            ],
            '1col 5ind' => [
                5,
                ['bbb'],
                '     $this->addPrimaryKey(\'pk\', \'test\', [\'bbb\']);',
                '     $this->dropPrimaryKey(\'pk\', \'test\');',
            ],
            '2col 0ind' => [
                0,
                ['ccc', 'ddd'],
                '$this->addPrimaryKey(\'pk\', \'test\', [\'ccc\', \'ddd\']);',
                '$this->dropPrimaryKey(\'pk\', \'test\');',
            ],
            '3col 4ind' => [
                4,
                ['eee', 'fff', 'ggg'],
                '    $this->addPrimaryKey(\'pk\', \'test\', [\'eee\', \'fff\', \'ggg\']);',
                '    $this->dropPrimaryKey(\'pk\', \'test\');',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param array $columns
     * @param string $expectedAdd
     * @param string $expectedDrop
     */
    public function shouldRenderProperlyPrimaryKey(
        int $indent,
        array $columns,
        string $expectedAdd,
        string $expectedDrop
    ): void {
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);
        $primaryKey->method('getColumns')->willReturn($columns);
        $primaryKey->method('getName')->willReturn('pk');

        $this->assertSame($expectedAdd, $this->renderer->renderUp($primaryKey, 'test', $indent));
        $this->assertSame($expectedDrop, $this->renderer->renderDown($primaryKey, 'test', $indent));
    }
}
