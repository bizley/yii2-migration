<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\BlueprintRenderer;
use bizley\migration\renderers\ColumnRendererInterface;
use bizley\migration\renderers\ForeignKeyRendererInterface;
use bizley\migration\renderers\IndexRendererInterface;
use bizley\migration\renderers\PrimaryKeyRendererInterface;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\PrimaryKeyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlueprintRendererTest extends TestCase
{
    /** @var BlueprintRenderer */
    private $renderer;

    /** @var ColumnRendererInterface|MockObject */
    private $columnRenderer;

    /** @var PrimaryKeyRendererInterface|MockObject */
    private $primaryKeyRenderer;

    /** @var IndexRendererInterface|MockObject */
    private $indexRenderer;

    /** @var ForeignKeyRendererInterface|MockObject */
    private $foreignKeyRenderer;

    /** @var BlueprintInterface|MockObject */
    private $blueprint;

    protected function setUp(): void
    {
        $this->columnRenderer = $this->createMock(ColumnRendererInterface::class);
        $this->primaryKeyRenderer = $this->createMock(PrimaryKeyRendererInterface::class);
        $this->indexRenderer = $this->createMock(IndexRendererInterface::class);
        $this->foreignKeyRenderer = $this->createMock(ForeignKeyRendererInterface::class);
        $this->renderer = new BlueprintRenderer(
            $this->columnRenderer,
            $this->primaryKeyRenderer,
            $this->indexRenderer,
            $this->foreignKeyRenderer
        );
        $this->blueprint = $this->createMock(BlueprintInterface::class);
        $this->blueprint->method('getTableName')->willReturn('table');
    }

    public function providerForName(): array
    {
        return [
            'not use prefix' => [false, null, 'table', 'table'],
            'use prefix no db prefix' => [true, null, 'table', '{{%table}}'],
            'use prefix db prefix not included' => [true, 'prefix_', 'table', '{{%table}}'],
            'use prefix db prefix included' => [true, 'prefix_', 'prefix_table', '{{%table}}'],
            'use prefix db utf8 prefix included' => [true, 'łap_', 'łap_table', '{{%table}}'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForName
     * @param bool $usePrefix
     * @param string|null $dbPrefix
     * @param string $name
     * @param string|null $expected
     */
    public function shouldProperlyRenderName(bool $usePrefix, ?string $dbPrefix, string $name, ?string $expected): void
    {
        $this->assertSame($expected, $this->renderer->renderName($name, $usePrefix, $dbPrefix));
    }

    /** @test */
    public function shouldRenderUpProperlyEmpty(): void
    {
        $this->assertSame('', $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyEmpty(): void
    {
        $this->assertSame('', $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyColumnsToDrop(): void
    {
        $this->columnRenderer->method('renderDrop')->willReturn('drop-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getDroppedColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("drop-column\ndrop-column", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyColumnsToDrop(): void
    {
        $this->columnRenderer->method('renderDrop')->willReturn('drop-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getAddedColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("drop-column\ndrop-column", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyColumnsToAdd(): void
    {
        $this->columnRenderer->method('renderAdd')->willReturn('add-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getAddedColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("add-column\nadd-column", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyColumnsToAdd(): void
    {
        $this->columnRenderer->method('renderAdd')->willReturn('add-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getDroppedColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("add-column\nadd-column", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyColumnsToAlter(): void
    {
        $this->columnRenderer->method('renderAlter')->willReturn('alter-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getAlteredColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("alter-column\nalter-column", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyColumnsToAlter(): void
    {
        $this->columnRenderer->method('renderAlter')->willReturn('alter-column');
        $column = $this->createMock(ColumnInterface::class);
        $this->blueprint->method('getUnalteredColumns')->willReturn(
            [
                'col1' => $column,
                'col2' => $column,
            ]
        );
        $this->assertSame("alter-column\nalter-column", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyForeignKeysToDrop(): void
    {
        $this->foreignKeyRenderer->method('renderDown')->willReturn('drop-fk');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $this->blueprint->method('getDroppedForeignKeys')->willReturn(
            [
                'fk1' => $foreignKey,
                'fk2' => $foreignKey,
            ]
        );
        $this->assertSame("drop-fk\ndrop-fk", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyForeignKeysToDrop(): void
    {
        $this->foreignKeyRenderer->method('renderDown')->willReturn('drop-fk');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $this->blueprint->method('getAddedForeignKeys')->willReturn(
            [
                'fk1' => $foreignKey,
                'fk2' => $foreignKey,
            ]
        );
        $this->assertSame("drop-fk\ndrop-fk", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyForeignKeysToAdd(): void
    {
        $this->foreignKeyRenderer->method('renderUp')->willReturn('add-fk');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $this->blueprint->method('getAddedForeignKeys')->willReturn(
            [
                'fk1' => $foreignKey,
                'fk2' => $foreignKey,
            ]
        );
        $this->assertSame("add-fk\nadd-fk", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyForeignKeysToAdd(): void
    {
        $this->foreignKeyRenderer->method('renderUp')->willReturn('add-fk');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $this->blueprint->method('getDroppedForeignKeys')->willReturn(
            [
                'fk1' => $foreignKey,
                'fk2' => $foreignKey,
            ]
        );
        $this->assertSame("add-fk\nadd-fk", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyIndexesToDrop(): void
    {
        $this->indexRenderer->method('renderDown')->willReturn('drop-idx');
        $index = $this->createMock(IndexInterface::class);
        $this->blueprint->method('getDroppedIndexes')->willReturn(
            [
                'idx1' => $index,
                'idx2' => $index,
            ]
        );
        $this->assertSame("drop-idx\ndrop-idx", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyIndexesToDrop(): void
    {
        $this->indexRenderer->method('renderDown')->willReturn('drop-idx');
        $index = $this->createMock(IndexInterface::class);
        $this->blueprint->method('getAddedIndexes')->willReturn(
            [
                'idx1' => $index,
                'idx2' => $index,
            ]
        );
        $this->assertSame("drop-idx\ndrop-idx", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyIndexesToAdd(): void
    {
        $this->indexRenderer->method('renderUp')->willReturn('create-idx');
        $index = $this->createMock(IndexInterface::class);
        $this->blueprint->method('getAddedIndexes')->willReturn(
            [
                'idx1' => $index,
                'idx2' => $index,
            ]
        );
        $this->assertSame("create-idx\ncreate-idx", $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyIndexesToAdd(): void
    {
        $this->indexRenderer->method('renderUp')->willReturn('create-idx');
        $index = $this->createMock(IndexInterface::class);
        $this->blueprint->method('getDroppedIndexes')->willReturn(
            [
                'idx1' => $index,
                'idx2' => $index,
            ]
        );
        $this->assertSame("create-idx\ncreate-idx", $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyPrimaryKeyToDrop(): void
    {
        $this->primaryKeyRenderer->method('renderDown')->willReturn('drop-pk');
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $this->blueprint->method('getAddedPrimaryKey')->willReturn($primaryKey);
        $this->assertSame('drop-pk', $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyPrimaryKeyToDrop(): void
    {
        $this->primaryKeyRenderer->method('renderDown')->willReturn('drop-pk');
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $this->blueprint->method('getDroppedPrimaryKey')->willReturn($primaryKey);
        $this->assertSame('drop-pk', $this->renderer->renderDown($this->blueprint));
    }

    /** @test */
    public function shouldRenderUpProperlyPrimaryKeyToAdd(): void
    {
        $this->primaryKeyRenderer->method('renderUp')->willReturn('add-pk');
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $this->blueprint->method('getAddedPrimaryKey')->willReturn($primaryKey);
        $this->assertSame('add-pk', $this->renderer->renderUp($this->blueprint));
    }

    /** @test */
    public function shouldRenderDownProperlyPrimaryKeyToAdd(): void
    {
        $this->primaryKeyRenderer->method('renderUp')->willReturn('add-pk');
        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $this->blueprint->method('getDroppedPrimaryKey')->willReturn($primaryKey);
        $this->assertSame('add-pk', $this->renderer->renderDown($this->blueprint));
    }
}
