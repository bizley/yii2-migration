<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\IndexRenderer;
use bizley\migration\table\IndexInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group renderers
 * @group indexrenderer
 */
final class IndexRendererTest extends TestCase
{
    use PhpStringLiteralsTrait;

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

        self::assertSame('$this->createIndex(\'pk\', \'test\', []);', $this->renderer->renderUp($index, 'test'));
    }

    /** @test */
    public function shouldRenderProperTemplateForDown(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('pk');

        self::assertSame('$this->dropIndex(\'pk\', \'test\');', $this->renderer->renderDown($index, 'test'));
    }

    /**
     * @return array<string, array{int, list<string>, bool, string, string}>
     */
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
     * @param list<string> $columns
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

        self::assertSame($expectedCreate, $this->renderer->renderUp($index, 'test', $indent));
        self::assertSame($expectedDrop, $this->renderer->renderDown($index, 'test', $indent));
    }

    /** @test */
    public function shouldEscapeNamesInRenderedCode(): void
    {
        $name = self::$unusualName;
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([$name, 'b']);
        $index->method('getName')->willReturn($name);
        $index->method('isUnique')->willReturn(false);

        self::assertSame([$name, $name, $name, 'b'], self::stringLiterals($this->renderer->renderUp($index, $name)));
        self::assertSame([$name, $name], self::stringLiterals($this->renderer->renderDown($index, $name)));
    }

    /** @test */
    public function shouldRenderIndexMethod(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn(['aaa']);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn(false);
        $index->method('getMethod')->willReturn('hash');

        self::assertSame(
            '$this->createIndex(\'idx\', \'test\', [\'aaa\'], \'hash\');',
            $this->renderer->renderUp($index, 'test')
        );
    }

    /** @test */
    public function shouldRenderIndexWithDefinition(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([]);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn(false);
        $index->method('getDefinition')->willReturn('USING gin (name gin_trgm_ops)');

        self::assertSame(
            '    $this->execute(\'CREATE INDEX [[idx]] ON {{%test}} USING gin (name gin_trgm_ops)\');',
            $this->renderer->renderUp($index, '{{%test}}', 4)
        );
    }

    /** @test */
    public function shouldRenderUniqueIndexWithDefinitionAsValidPhp(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([]);
        $index->method('getName')->willReturn('idx');
        $index->method('isUnique')->willReturn(true);
        $index->method('getDefinition')->willReturn("USING btree (a) WHERE ((a)::text <> 'it''s C:\\dir'::text)");

        $rendered = $this->renderer->renderUp($index, 'test');
        self::assertSame(
            '$this->execute(\'CREATE UNIQUE INDEX [[idx]] ON test USING btree (a) '
            . 'WHERE ((a)::text <> \\\'it\\\'\\\'s C:\\\\dir\\\'::text)\');',
            $rendered
        );
        self::assertSame(
            "CREATE UNIQUE INDEX [[idx]] ON test USING btree (a) WHERE ((a)::text <> 'it''s C:\\dir'::text)",
            eval('return ' . \substr($rendered, \strlen('$this->execute('), -2) . ';')
        );
    }

    /** @test */
    public function shouldRenderDropForIndexWithDefinition(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getName')->willReturn('idx');
        $index->method('getDefinition')->willReturn('USING gin (a)');

        self::assertSame('$this->dropIndex(\'idx\', \'test\');', $this->renderer->renderDown($index, 'test'));
    }

    /** @test */
    public function shouldRenderIndexWithDefinitionAndUnusualNamesAsValidPhpAndSql(): void
    {
        $index = $this->createMock(IndexInterface::class);
        $index->method('getColumns')->willReturn([]);
        $index->method('getName')->willReturn('i\'dx "{tableName}"\\');
        $index->method('isUnique')->willReturn(false);
        $index->method('getDefinition')->willReturn('USING gin (a)');

        $rendered = $this->renderer->renderUp($index, 't\'ab');
        self::assertSame(
            'CREATE INDEX "i\'dx ""{tableName}""\\" ON t\'ab USING gin (a)',
            eval('return ' . \substr($rendered, \strlen('$this->execute('), -2) . ';')
        );
    }
}
