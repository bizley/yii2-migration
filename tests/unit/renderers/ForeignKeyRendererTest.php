<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\ForeignKeyRenderer;
use bizley\migration\Schema;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\ForeignKeyInterface;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;

/**
 * @group renderers
 * @group foreignkeyrenderer
 */
final class ForeignKeyRendererTest extends TestCase
{
    /** @var ForeignKeyRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ForeignKeyRenderer(true);
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldThrowExceptionForSQLiteAndNonGeneralSchemaRenderUp(): void
    {
        $this->expectException(NotSupportedException::class);

        (new ForeignKeyRenderer(false))->renderUp(
            $this->createMock(ForeignKeyInterface::class),
            'table',
            'refTable',
            0,
            Schema::SQLITE
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldThrowExceptionForSQLiteAndNonGeneralSchemaRenderDown(): void
    {
        $this->expectException(NotSupportedException::class);

        (new ForeignKeyRenderer(false))->renderDown(
            $this->createMock(ForeignKeyInterface::class),
            'table',
            0,
            Schema::SQLITE
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldRenderProperTemplateForUp(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn([]);
        $foreignKey->method('getReferredColumns')->willReturn([]);
        $foreignKey->method('getOnDelete')->willReturn(null);
        $foreignKey->method('getOnUpdate')->willReturn(null);
        $foreignKey->method('getName')->willReturn('fk');

        $this->assertSame(
            '$this->addForeignKey(
    \'fk\',
    \'table\',
    [],
    \'refTable\',
    [],
    null,
    null
);',
            $this->renderer->renderUp($foreignKey, 'table', 'refTable')
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldRenderProperTemplateForDown(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn('fk');

        $this->assertSame(
            '$this->dropForeignKey(\'fk\', \'table\');',
            $this->renderer->renderDown($foreignKey, 'table')
        );
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
                <<<'RENDERED'
$this->addForeignKey(
    'fk',
    'test',
    ['a'],
    'refTable',
    ['b'],
    null,
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk\', \'test\');'
            ],
            '#2' => [
                4,
                ['c'],
                ['d'],
                null,
                null,
                <<<'RENDERED'
    $this->addForeignKey(
        'fk',
        'test',
        ['c'],
        'refTable',
        ['d'],
        null,
        null
    );
RENDERED
                ,
                '    $this->dropForeignKey(\'fk\', \'test\');'
            ],
            '#3' => [
                0,
                ['a', 'b'],
                ['c'],
                'abc',
                'eee',
                <<<'RENDERED'
$this->addForeignKey(
    'fk',
    'test',
    ['a', 'b'],
    'refTable',
    ['c'],
    'abc',
    'eee'
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk\', \'test\');'
            ],
            '#4' => [
                0,
                ['a', 'b'],
                ['c', 'd'],
                'a',
                '',
                <<<'RENDERED'
$this->addForeignKey(
    'fk',
    'test',
    ['a', 'b'],
    'refTable',
    ['c', 'd'],
    'a',
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk\', \'test\');'
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
     * @param string $expectedAdd
     * @param string $expectedDrop
     * @throws NotSupportedException
     */
    public function shouldRenderProperlyForeignKey(
        int $indent,
        array $columns,
        array $refColumns,
        ?string $onDelete,
        ?string $onUpdate,
        string $expectedAdd,
        string $expectedDrop
    ): void {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn($columns);
        $foreignKey->method('getReferredColumns')->willReturn($refColumns);
        $foreignKey->method('getOnDelete')->willReturn($onDelete);
        $foreignKey->method('getOnUpdate')->willReturn($onUpdate);
        $foreignKey->method('getName')->willReturn('fk');

        $this->assertSame($expectedAdd, $this->renderer->renderUp($foreignKey, 'test', 'refTable', $indent));
        $this->assertSame($expectedDrop, $this->renderer->renderDown($foreignKey, 'test', $indent));
    }

    public function providerForRenderName(): array
    {
        return [
            'not numeric' => [
                'aaa',
                [],
                <<<'RENDERED'
$this->addForeignKey(
    'aaa',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'aaa\', \'test\');'
            ],
            'numeric no columns' => [
                '123',
                [],
                <<<'RENDERED'
$this->addForeignKey(
    'fk-test-',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk-test-\', \'test\');'
            ],
            'null no columns' => [
                null,
                [],
                <<<'RENDERED'
$this->addForeignKey(
    'fk-test-',
    'test',
    [],
    'refTable',
    [],
    null,
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk-test-\', \'test\');'
            ],
            'null columns' => [
                null,
                ['a', 'b'],
                <<<'RENDERED'
$this->addForeignKey(
    'fk-test-a-b',
    'test',
    ['a', 'b'],
    'refTable',
    [],
    null,
    null
);
RENDERED
                ,
                '$this->dropForeignKey(\'fk-test-a-b\', \'test\');'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRenderName
     * @param string|null $name
     * @param array $columns
     * @param string $expectedAdd
     * @param string $expectedDrop
     * @throws NotSupportedException
     */
    public function shouldRenderProperlyForeignKeyName(
        ?string $name,
        array $columns,
        string $expectedAdd,
        string $expectedDrop
    ): void {
        $foreignKey = new ForeignKey();
        $foreignKey->setColumns($columns);
        $foreignKey->setName($name);
        $foreignKey->setTableName('test');

        $this->assertSame($expectedAdd, $this->renderer->renderUp($foreignKey, 'test', 'refTable'));
        $this->assertSame($expectedDrop, $this->renderer->renderDown($foreignKey, 'test'));
    }
}
