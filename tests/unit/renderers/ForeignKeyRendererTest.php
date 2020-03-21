<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\ForeignKeyRenderer;
use bizley\migration\table\ForeignKeyInterface;
use PHPUnit\Framework\TestCase;

class ForeignKeyRendererTest extends TestCase
{
    /** @var ForeignKeyRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ForeignKeyRenderer();
    }

    /**
     * @test
     */
    public function shouldReturnNullWhenNoForeignKey(): void
    {
        $this->assertNull($this->renderer->render('test', 'refTable'));
    }

    /**
     * @test
     */
    public function shouldRenderProperTemplate(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn([]);
        $foreignKey->method('getReferencedColumns')->willReturn([]);
        $foreignKey->method('getOnDelete')->willReturn(null);
        $foreignKey->method('getOnUpdate')->willReturn(null);
        $foreignKey->method('getName')->willReturn('fk');

        $this->renderer->setForeignKey($foreignKey);
        $this->renderer->setAddKeyTemplate('new-template');
        $this->assertSame('new-template', $this->renderer->render('test', 'refTable'));
    }

    public function providerForRender(): array
    {
        return [
            '#1' => [
                0,
                ['a'],
                ['b'],
                null,
                null,
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk',
    'test',
    ['a'],
    'refTable',
    ['b'],
    null,
    null
);
TEMPLATE
            ],
            '#2' => [
                4,
                ['c'],
                ['d'],
                null,
                null,
                <<<'TEMPLATE'
    $this->addForeignKey(
        'fk',
        'test',
        ['c'],
        'refTable',
        ['d'],
        null,
        null
    );
TEMPLATE
            ],
            '#3' => [
                0,
                ['a', 'b'],
                ['c'],
                'abc',
                'eee',
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk',
    'test',
    ['a', 'b'],
    'refTable',
    ['c'],
    'abc',
    'eee'
);
TEMPLATE
            ],
            '#4' => [
                0,
                ['a', 'b'],
                ['c', 'd'],
                'a',
                '',
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk',
    'test',
    ['a', 'b'],
    'refTable',
    ['c', 'd'],
    'a',
    null
);
TEMPLATE
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param array $columns
     * @param array $refColumns
     * @param string|null $onDelete
     * @param string|null $onUpdate
     * @param string $expected
     */
    public function shouldRenderProperlyForeignKey(
        int $indent,
        array $columns,
        array $refColumns,
        ?string $onDelete,
        ?string $onUpdate,
        string $expected
    ): void {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn($columns);
        $foreignKey->method('getReferencedColumns')->willReturn($refColumns);
        $foreignKey->method('getOnDelete')->willReturn($onDelete);
        $foreignKey->method('getOnUpdate')->willReturn($onUpdate);
        $foreignKey->method('getName')->willReturn('fk');

        $this->renderer->setForeignKey($foreignKey);
        $this->assertSame($expected, $this->renderer->render('test', 'refTable', $indent));
    }

    public function providerForRenderName(): array
    {
        return [
            'not numeric' => [
                'aaa',
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'aaa',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'numeric no columns' => [
                '123',
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk-test-',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'null no columns' => [
                null,
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk-test-',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'null columns' => [
                null,
                ['a', 'b'],
                <<<'TEMPLATE'
$this->addForeignKey(
    'fk-test-a-b',
    'test',
    ['a', 'b'],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRenderName
     * @param string|null $name
     * @param array $columns
     * @param string $expected
     */
    public function shouldRenderProperlyForeignKeyName(?string $name, array $columns, string $expected): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn($columns);
        $foreignKey->method('getReferencedColumns')->willReturn([]);
        $foreignKey->method('getOnDelete')->willReturn(null);
        $foreignKey->method('getOnUpdate')->willReturn(null);
        $foreignKey->method('getName')->willReturn($name);

        $this->renderer->setForeignKey($foreignKey);
        $this->assertSame($expected, $this->renderer->render('test', 'refTable'));
    }

    public function providerForRenderNameWithCustomTemplate(): array
    {
        return [
            'not numeric' => [
                'aaa',
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'aaa',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'numeric no columns' => [
                '123',
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'key-template',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'null no columns' => [
                null,
                [],
                <<<'TEMPLATE'
$this->addForeignKey(
    'key-template',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
            'null columns' => [
                null,
                ['a', 'b'],
                <<<'TEMPLATE'
$this->addForeignKey(
    'key-template',
    'test',
    ['a', 'b'],
    'refTable',
    [],
    null,
    null
);
TEMPLATE
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRenderNameWithCustomTemplate
     * @param string|null $name
     * @param array $columns
     * @param string $expected
     */
    public function shouldRenderProperlyForeignKeyNameWithCustomTemplate(
        ?string $name,
        array $columns,
        string $expected
    ): void {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn($columns);
        $foreignKey->method('getReferencedColumns')->willReturn([]);
        $foreignKey->method('getOnDelete')->willReturn(null);
        $foreignKey->method('getOnUpdate')->willReturn(null);
        $foreignKey->method('getName')->willReturn($name);

        $this->renderer->setForeignKey($foreignKey);
        $this->renderer->setKeyNameTemplate('key-template');
        $this->assertSame($expected, $this->renderer->render('test', 'refTable'));
    }
}
