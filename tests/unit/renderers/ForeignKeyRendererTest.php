<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\ForeignKeyRenderer;
use bizley\migration\Schema;
use bizley\migration\table\ForeignKey;
use bizley\migration\table\ForeignKeyInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\base\NotSupportedException;

#[Group('renderers')]
#[Group('foreignkeyrenderer')]
final class ForeignKeyRendererTest extends TestCase
{
    use PhpStringLiteralsTrait;

    /** @var ForeignKeyRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ForeignKeyRenderer(true);
    }

    /**
     * @throws NotSupportedException
     */
    #[Test]
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
     * @throws NotSupportedException
     */
    #[Test]
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
     * @throws NotSupportedException
     */
    #[Test]
    public function shouldRenderProperTemplateForUp(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn([]);
        $foreignKey->method('getReferredColumns')->willReturn([]);
        $foreignKey->method('getOnDelete')->willReturn(null);
        $foreignKey->method('getOnUpdate')->willReturn(null);
        $foreignKey->method('getName')->willReturn('fk');

        self::assertSame(
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
     * @throws NotSupportedException
     */
    #[Test]
    public function shouldRenderProperTemplateForDown(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn('fk');

        self::assertSame(
            '$this->dropForeignKey(\'fk\', \'table\');',
            $this->renderer->renderDown($foreignKey, 'table')
        );
    }

    /**
     * @return array<string, array{int, list<string>, list<string>, string|null, string|null, string, string}>
     */
    public static function providerForRender(): array
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
     * @param int $indent
     * @param list<string> $columns
     * @param list<string> $refColumns
     * @param string|null $onDelete
     * @param string|null $onUpdate
     * @param string $expectedAdd
     * @param string $expectedDrop
     * @throws NotSupportedException
     */
    #[DataProvider('providerForRender')]
    #[Test]
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

        self::assertSame($expectedAdd, $this->renderer->renderUp($foreignKey, 'test', 'refTable', $indent));
        self::assertSame($expectedDrop, $this->renderer->renderDown($foreignKey, 'test', $indent));
    }

    /**
     * @return array<string, array{string|null, list<string>, string, string}>
     */
    public static function providerForRenderName(): array
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
     * @param string|null $name
     * @param list<string> $columns
     * @param string $expectedAdd
     * @param string $expectedDrop
     * @throws NotSupportedException
     */
    #[DataProvider('providerForRenderName')]
    #[Test]
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

        self::assertSame($expectedAdd, $this->renderer->renderUp($foreignKey, 'test', 'refTable'));
        self::assertSame($expectedDrop, $this->renderer->renderDown($foreignKey, 'test'));
    }

    #[Test]
    public function shouldEscapeNamesInRenderedCode(): void
    {
        $name = self::$unusualName;
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getName')->willReturn($name);
        $foreignKey->method('getColumns')->willReturn([$name, 'b']);
        $foreignKey->method('getReferredColumns')->willReturn(['c', $name]);
        $foreignKey->method('getOnDelete')->willReturn($name);
        $foreignKey->method('getOnUpdate')->willReturn('CASCADE');

        self::assertSame(
            [$name, $name, $name, 'b', $name, 'c', $name, $name, 'CASCADE'],
            self::stringLiterals($this->renderer->renderUp($foreignKey, $name, $name))
        );
        self::assertSame([$name, $name], self::stringLiterals($this->renderer->renderDown($foreignKey, $name)));
    }

    /**
     * @throws NotSupportedException
     */
    #[Test]
    public function shouldTreatNegativeIndentAsNoIndent(): void
    {
        $foreignKey = $this->createMock(ForeignKeyInterface::class);
        $foreignKey->method('getColumns')->willReturn(['a']);
        $foreignKey->method('getReferredColumns')->willReturn(['b']);
        $foreignKey->method('getName')->willReturn('fk');

        self::assertSame(
            $this->renderer->renderUp($foreignKey, 'test', 'refTable'),
            $this->renderer->renderUp($foreignKey, 'test', 'refTable', -4)
        );
        self::assertSame(
            $this->renderer->renderDown($foreignKey, 'test'),
            $this->renderer->renderDown($foreignKey, 'test', -4)
        );
    }
}
