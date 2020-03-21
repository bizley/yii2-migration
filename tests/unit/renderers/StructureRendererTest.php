<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\ColumnRendererInterface;
use bizley\migration\renderers\ForeignKeyRendererInterface;
use bizley\migration\renderers\IndexRendererInterface;
use bizley\migration\renderers\PrimaryKeyRendererInterface;
use bizley\migration\renderers\StructureRenderer;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\IndexInterface;
use bizley\migration\table\StructureInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StructureRendererTest extends TestCase
{
    /** @var StructureRenderer */
    private $structureRenderer;
    /** @var ColumnRendererInterface|MockObject */
    private $columnRenderer;
    /** @var PrimaryKeyRendererInterface|MockObject */
    private $primaryKeyRenderer;
    /** @var IndexRendererInterface|MockObject */
    private $indexRenderer;
    /** @var ForeignKeyRendererInterface|MockObject */
    private $foreignKeyRenderer;

    protected function setUp(): void
    {
        $this->columnRenderer = $this->createMock(ColumnRendererInterface::class);
        $this->primaryKeyRenderer = $this->createMock(PrimaryKeyRendererInterface::class);
        $this->indexRenderer = $this->createMock(IndexRendererInterface::class);
        $this->foreignKeyRenderer = $this->createMock(ForeignKeyRendererInterface::class);
        $this->structureRenderer = new StructureRenderer(
            $this->columnRenderer,
            $this->primaryKeyRenderer,
            $this->indexRenderer,
            $this->foreignKeyRenderer
        );
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
     * @param string|null $name
     * @param string|null $expected
     */
    public function shouldProperlyRenderName(bool $usePrefix, ?string $dbPrefix, ?string $name, ?string $expected): void
    {
        $this->structureRenderer->setUsePrefix($usePrefix);
        $this->structureRenderer->setDbPrefix($dbPrefix);

        $this->assertSame($expected, $this->structureRenderer->renderName($name));
    }

    /**
     * @test
     */
    public function shouldRenderNothingWhenThereIsNoStructure(): void
    {
        $this->assertSame('', $this->structureRenderer->renderStructure('', null, true));
        $this->assertSame('', $this->structureRenderer->renderStructure('', null, false));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithTable(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getColumns')->willReturn([$this->createMock(ColumnInterface::class)]);
        $structure->method('getName')->willReturn('table');
        $this->structureRenderer->setStructure($structure);
        $this->columnRenderer->method('render')->willReturn('column-render');

        $this->assertSame(
            <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{{%table}}',
    [
column-render
    ],
    $tableOptions
);
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', null, true)
        );
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithIndent(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getColumns')->willReturn([]);
        $structure->method('getName')->willReturn('table');
        $this->structureRenderer->setStructure($structure);

        $this->assertSame(
            <<<'TEMPLATE'
    $tableOptions = null;
    if ($this->db->driverName === 'mysql') {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
    }

    $this->createTable(
        '{{%table}}',
        [
    
        ],
        $tableOptions
    );
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', '', true, 4)
        );
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithTableAndCustomTemplate(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getColumns')->willReturn([]);
        $this->structureRenderer->setStructure($structure);
        $this->structureRenderer->setCreateTableTemplate('custom-template');

        $this->assertSame('custom-template', $this->structureRenderer->renderStructure('', null, true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithPrimaryKey(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $this->primaryKeyRenderer->method('render')->willReturn('primary-key-render');
        $this->structureRenderer->setStructure($structure);

        $this->assertSame(
            <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{{%table}}',
    [

    ],
    $tableOptions
);

primary-key-render
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', null, true)
        );
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithIndexes(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $index = $this->createMock(IndexInterface::class);
        $structure->method('getIndexes')->willReturn([$index, $index]);
        $this->indexRenderer->method('render')->willReturn('index-render');
        $this->structureRenderer->setStructure($structure);

        $this->assertSame(
            <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{{%table}}',
    [

    ],
    $tableOptions
);

index-render
index-render
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', null, true)
        );
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithNoIndexForForeignKey(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $index1 = $this->createMock(IndexInterface::class);
        $index1->method('getName')->willReturn('same-name');
        $index2 = $this->createMock(IndexInterface::class);
        $structure->method('getIndexes')->willReturn([$index1, $index2]);
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn('same-name');
        $structure->method('getForeignKeys')->willReturn([$foreignKey]);
        $this->indexRenderer->method('render')->willReturn('index-render');
        $this->structureRenderer->setStructure($structure);

        $this->assertSame(
            <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{{%table}}',
    [

    ],
    $tableOptions
);

index-render
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', null, true)
        );
    }

    /**
     * @test
     */
    public function shouldRenderProperlyWithForeignKeys(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $structure->method('getForeignKeys')->willReturn([$foreignKey]);
        $this->foreignKeyRenderer->method('render')->willReturn('foreign-key-render');
        $this->structureRenderer->setStructure($structure);

        $this->assertSame(
            <<<'TEMPLATE'
$tableOptions = null;
if ($this->db->driverName === 'mysql') {
    $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
}

$this->createTable(
    '{{%table}}',
    [

    ],
    $tableOptions
);

foreign-key-render
TEMPLATE
            ,
            $this->structureRenderer->renderStructure('', null, true)
        );
    }
}
