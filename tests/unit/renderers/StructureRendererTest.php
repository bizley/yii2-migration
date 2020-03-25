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
        $this->assertSame($expected, $this->structureRenderer->renderName($name, $usePrefix, $dbPrefix));
    }

    /** @test */
    public function shouldRenderProperlyWithTableForUp(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getColumns')->willReturn([$this->createMock(ColumnInterface::class)]);
        $structure->method('getName')->willReturn('table');
        $this->columnRenderer->method('render')->willReturn('column-render');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure)
        );
    }

    /** @test */
    public function shouldRenderProperlyWithTableForDown(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $this->columnRenderer->method('render')->willReturn('column-render');

        $this->assertSame(
            '$this->dropTable(\'{{%table}}\');',
            $this->structureRenderer->renderStructureDown($structure)
        );
    }

    /** @test */
    public function shouldRenderProperlyWithIndent(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getColumns')->willReturn([]);
        $structure->method('getName')->willReturn('table');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure, 4)
        );
    }

    /** @test */
    public function shouldRenderProperlyWithTableAndCustomCreateTemplate(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $this->structureRenderer->setCreateTableTemplate('custom-template');

        $this->assertSame('custom-template', $this->structureRenderer->renderStructureUp($structure));
    }

    /** @test */
    public function shouldRenderProperlyWithTableAndCustomDropTemplate(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $this->structureRenderer->setDropTableTemplate('custom-template');

        $this->assertSame('custom-template', $this->structureRenderer->renderStructureDown($structure));
    }

    /** @test */
    public function shouldRenderProperlyWithPrimaryKey(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $this->primaryKeyRenderer->method('renderUp')->willReturn('primary-key-render');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure)
        );
    }

    /** @test */
    public function shouldRenderProperlyWithIndexes(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $index = $this->createMock(IndexInterface::class);
        $structure->method('getIndexes')->willReturn([$index, $index]);
        $this->indexRenderer->method('renderUp')->willReturn('index-render');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure)
        );
    }

    /** @test */
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
        $this->indexRenderer->method('renderUp')->willReturn('index-render');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure)
        );
    }

    /** @test */
    public function shouldRenderProperlyWithForeignKeys(): void
    {
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $structure->method('getForeignKeys')->willReturn([$foreignKey]);
        $this->foreignKeyRenderer->method('renderUp')->willReturn('foreign-key-render');

        $this->assertSame(
            <<<'RENDERED'
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
RENDERED
            ,
            $this->structureRenderer->renderStructureUp($structure)
        );
    }

    /** @test */
    public function shouldRenderProperlyForeignKeysDown(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $this->foreignKeyRenderer->method('renderDown')->willReturn('foreign-key-render');

        $this->assertSame('foreign-key-render', $this->structureRenderer->renderForeignKeysDown([$foreignKey]));
    }
}
