<?php

declare(strict_types=1);

namespace bizley\tests\unit\renderers;

use bizley\migration\renderers\ColumnRenderer;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;
use bizley\migration\table\PrimaryKeyVariantColumnInterface;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;

final class ColumnRendererTest extends TestCase
{
    protected function getRenderer(bool $generalSchema = true): ColumnRenderer
    {
        return new ColumnRenderer($generalSchema);
    }

    public function providerForEscaping(): array
    {
        return [
            ['aaa', 'aaa'],
            ['\\\'aaa', '\'aaa'],
            ['\\\'aaa\\\'', '\'aaa\''],
            [null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForEscaping
     * @param string|null $expected
     * @param string|null $input
     */
    public function shouldProperlyEscapeQuotes(?string $expected, ?string $input): void
    {
        $this->assertSame($expected, $this->getRenderer()->escapeQuotes($input));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithNoLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyColumnWithNoLength(): void
    {
        $column = $this->createMock(PrimaryKeyColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthNoPKAndNonGeneralSchema(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer(false)->render($column));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithPKButNoLengthAndNonGeneralSchema(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer(false)->render($column, $primaryKey));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthAndGeneralSchemaAndNoPrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthGeneralSchemaNonCompositePrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column, $primaryKey));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthGeneralSchemaNonColumnInPrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isColumnInPrimaryKey')->willReturn(false);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column, $primaryKey));
    }

    /** @test */
    public function shouldRenderProperlyPrimaryKeyVariantColumn(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getPrimaryKeyDefinition')->willReturn('primaryKeyDef({renderLength})');
        $column->method('isColumnInPrimaryKey')->willReturn(true);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertSame('\'col\' => $this->primaryKeyDef(),', $this->getRenderer()->render($column, $primaryKey));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithNonGeneralSchemaAndNonDefaultLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn('12');

        $this->assertSame('\'col\' => $this->def(12),', $this->getRenderer(false)->render($column));
    }

    public function providerForDefaultLengths(): array
    {
        return [
            ['9', '(9)'],
            ['2 ', '(2)'],
            [' 3 ', '(3)'],
            ['4,1', '(4,1)'],
            ['5, 2', '(5,2)'],
            ['7 , 2', '(7,2)'],
            ['max', '(max)'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForDefaultLengths
     * @param string $length
     * @param string $mapping
     */
    public function shouldRenderProperlySimpleColumnWithGeneralSchemaAndDefaultLength(
        string $length,
        ?string $mapping
    ): void {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn($length);
        $column->method('getDefaultMapping')->willReturn($mapping);

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithGeneralSchemaAndDefaultLengthAsNull(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn(null);
        $column->method('getDefaultMapping')->willReturn(null);

        $this->assertSame('\'col\' => $this->def(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithGeneralSchemaAndNonDefaultLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn('11');
        $column->method('getDefaultMapping')->willReturn('(9,3)');

        $this->assertSame('\'col\' => $this->def(11),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithUnsigned(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isUnsigned')->willReturn(true);

        $this->assertSame('\'col\' => $this->def()->unsigned(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithDefaultExpression(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn(new Expression('TEST'));

        $this->assertSame(
            '\'col\' => $this->def()->defaultExpression(\'TEST\'),',
            $this->getRenderer()->render($column)
        );
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithDefaultArrayValue(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn([1, 2, 3]);

        $this->assertSame('\'col\' => $this->def()->defaultValue(\'[1,2,3]\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithDefaultValue(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn(123);

        $this->assertSame('\'col\' => $this->def()->defaultValue(\'123\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithComment(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getComment')->willReturn('test');

        $this->assertSame('\'col\' => $this->def()->comment(\'test\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn('first');

        $this->assertSame('\'col\' => $this->def()->after(\'first\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithFirstAndAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn('first');
        $column->method('isFirst')->willReturn(true);

        $this->assertSame('\'col\' => $this->def()->after(\'first\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithFirstAndNoAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn(null);
        $column->method('isFirst')->willReturn(true);

        $this->assertSame('\'col\' => $this->def()->first(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAppend(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');

        $this->assertSame('\'col\' => $this->def()->append(\'aaa\'),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAppendAndCompositePrimaryKey(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);

        $this->assertSame(
            '\'col\' => $this->def()->append(\'aaa\'),',
            $this->getRenderer()->render($column, $primaryKey)
        );
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAppendAndNoColumnPrimaryKey(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');
        $column->method('isColumnInPrimaryKey')->willReturn(false);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertSame(
            '\'col\' => $this->def()->append(\'aaa\'),',
            $this->getRenderer()->render($column, $primaryKey)
        );
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAppendAndPrimaryKeyAndNoSchemaAppend(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');
        $column->method('isColumnInPrimaryKey')->willReturn(true);
        $column->method('prepareSchemaAppend')->willReturn('');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertSame(
            '\'col\' => $this->def()->append(\'aaa\'),',
            $this->getRenderer()->render($column, $primaryKey)
        );
    }

    /** @test */
    public function shouldRenderProperlySimpleColumnWithAppendAndPrimaryKeyAndSchemaAppend(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');
        $column->method('isColumnInPrimaryKey')->willReturn(true);
        $column->method('prepareSchemaAppend')->willReturn('schema-append');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->assertSame(
            '\'col\' => $this->def()->append(\'schema-append aaa\'),',
            $this->getRenderer()->render($column, $primaryKey)
        );
    }

    /** @test */
    public function shouldRenderProperlyColumnToAdd(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame(
            '$this->addColumn(\'table\', \'col\', $this->def());',
            $this->getRenderer()->renderAdd($column, 'table')
        );
    }

    /** @test */
    public function shouldRenderProperlyColumnToAlter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->assertSame(
            '$this->alterColumn(\'table\', \'col\', $this->def());',
            $this->getRenderer()->renderAlter($column, 'table')
        );
    }

    /** @test */
    public function shouldRenderProperlyColumnToDrop(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');

        $this->assertSame(
            '$this->dropColumn(\'table\', \'col\');',
            $this->getRenderer()->renderDrop($column, 'table')
        );
    }

    /** @test */
    public function shouldRenderProperlyColumnWithLengthAsMax(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn('max');

        $this->assertSame(
            '\'col\' => $this->def(\'max\'),',
            $this->getRenderer()->render($column)
        );
    }

    /** @test */
    public function shouldRenderProperlyColumnWithNotNull(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isNotNull')->willReturn(true);

        $this->assertSame('\'col\' => $this->def()->notNull(),', $this->getRenderer()->render($column));
    }

    /** @test */
    public function shouldRenderProperlyColumnWithUnsigned(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isUnsigned')->willReturn(true);

        $this->assertSame('\'col\' => $this->def()->unsigned(),', $this->getRenderer()->render($column));
    }
}
