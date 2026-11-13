<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\CreateIndexParser;
use PHPUnit\Framework\TestCase;

/** @group table */
final class CreateIndexParserTest extends TestCase
{
    /**
     * @return array<string, array{string, array{table: string, name: string, unique: bool, definition: string}}>
     */
    public function providerForCreateIndex(): array
    {
        return [
            'generated' => [
                'CREATE INDEX [[idx]] ON {{%tab}} USING gin (name gin_trgm_ops)',
                ['table' => '{{%tab}}', 'name' => 'idx', 'unique' => false, 'definition' => 'USING gin (name gin_trgm_ops)'],
            ],
            'generated unique with where' => [
                'CREATE UNIQUE INDEX [[idx-a]] ON {{%tab}} USING btree (a) WHERE (a IS NOT NULL)',
                [
                    'table' => '{{%tab}}',
                    'name' => 'idx-a',
                    'unique' => true,
                    'definition' => 'USING btree (a) WHERE (a IS NOT NULL)',
                ],
            ],
            'pg_get_indexdef' => [
                'CREATE INDEX b189_expr_idx ON public.beneficiary USING btree (lower((name)::text))',
                [
                    'table' => 'public.beneficiary',
                    'name' => 'b189_expr_idx',
                    'unique' => false,
                    'definition' => 'USING btree (lower((name)::text))',
                ],
            ],
            'quoted identifiers' => [
                'CREATE INDEX "my ""idx""" ON "public"."tab" USING hash ("col")',
                ['table' => 'public.tab', 'name' => 'my "idx"', 'unique' => false, 'definition' => 'USING hash ("col")'],
            ],
            'lowercase, without method, with semicolon and whitespace' => [
                "  create index if not exists idx\non tab (a desc);  ",
                ['table' => 'tab', 'name' => 'idx', 'unique' => false, 'definition' => 'USING btree (a desc)'],
            ],
            'concurrently and only' => [
                'CREATE INDEX CONCURRENTLY idx ON ONLY tab using GIST (a)',
                ['table' => 'tab', 'name' => 'idx', 'unique' => false, 'definition' => 'USING GIST (a)'],
            ],
            'without method, with USING in predicate' => [
                "CREATE INDEX idx ON tab (a) WHERE (b <> 'USING x')",
                [
                    'table' => 'tab',
                    'name' => 'idx',
                    'unique' => false,
                    'definition' => "USING btree (a) WHERE (b <> 'USING x')",
                ],
            ],
            'table placeholder without prefix' => [
                'CREATE INDEX [[idx]] ON {{tab}} USING gin (a)',
                ['table' => '{{tab}}', 'name' => 'idx', 'unique' => false, 'definition' => 'USING gin (a)'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForCreateIndex
     * @param array{table: string, name: string, unique: bool, definition: string} $expected
     */
    public function shouldParseCreateIndex(string $sql, array $expected): void
    {
        self::assertSame($expected, CreateIndexParser::parse($sql));
    }

    /**
     * @return array<string, array{string}>
     */
    public function providerForNotCreateIndex(): array
    {
        return [
            'insert' => ['INSERT INTO tab (a) VALUES (1)'],
            'drop index' => ['DROP INDEX idx'],
            'no name' => ['CREATE INDEX ON tab (a)'],
            'no columns' => ['CREATE INDEX idx ON tab'],
            'create index in text' => ["UPDATE tab SET a = 'CREATE INDEX idx ON tab (a)'"],
        ];
    }

    /**
     * @test
     * @dataProvider providerForNotCreateIndex
     */
    public function shouldNotParseOtherStatements(string $sql): void
    {
        self::assertNull(CreateIndexParser::parse($sql));
    }
}
