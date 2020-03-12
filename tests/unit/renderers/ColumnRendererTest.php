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

class ColumnRendererTest extends TestCase
{
    /** @var ColumnRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ColumnRenderer();
    }

    /**
     * @test
     */
    public function shouldReturnNullWhenNoColumn(): void
    {
        $this->assertNull($this->renderer->render('test'));
    }

    public function providerForEscaping(): array
    {
        return [
            ['aaa', 'aaa'],
            ['\\\'aaa', '\'aaa'],
            ['\\\'aaa\\\'', '\'aaa\''],
        ];
    }

    /**
     * @test
     * @dataProvider providerForEscaping
     * @param string $expected
     * @param string $input
     */
    public function shouldProperlyEscapeQuotes(string $expected, string $input): void
    {
        $this->assertSame($expected, $this->renderer->escapeQuotes($input));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithNoLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyColumnWithNoLength(): void
    {
        $column = $this->createMock(PrimaryKeyColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthAndNonGeneralSchema(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test', false));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthAndGeneralSchemaAndNoPrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthGeneralSchemaNonCompositePrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyVariantColumnWithNoLengthGeneralSchemaNonColumnInPrimaryKey(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isColumnInPrimaryKey')->willReturn(false);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlyPrimaryKeyVariantColumn(): void
    {
        $column = $this->createMock(PrimaryKeyVariantColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getPrimaryKeyDefinition')->willReturn('primaryKeyDef({renderLength})');
        $column->method('isColumnInPrimaryKey')->willReturn(true);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->primaryKeyDef(),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithNonGeneralSchemaAndNonDefaultLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn('12');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(12),', $this->renderer->render('test', false));
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
        string $mapping
    ): void {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn($length);
        $column->method('getDefaultMapping')->willReturn($mapping);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithGeneralSchemaAndNonDefaultLength(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getLength')->willReturn('11');
        $column->method('getDefaultMapping')->willReturn('(9,3)');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def(11),', $this->renderer->render('test', true));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithUnsigned(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('isUnsigned')->willReturn(true);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->unsigned(),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithDefaultExpression(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn(new Expression('TEST'));

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->defaultExpression(\'TEST\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithDefaultArrayValue(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn([1, 2, 3]);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->defaultValue(\'[1,2,3]\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithDefaultValue(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getDefault')->willReturn(123);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->defaultValue(\'123\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithComment(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getComment')->willReturn('test');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->comment(\'test\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn('first');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->after(\'first\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithFirstAndAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn('first');
        $column->method('isFirst')->willReturn(true);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->after(\'first\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithFirstAndNoAfter(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAfter')->willReturn(null);
        $column->method('isFirst')->willReturn(true);

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->first(),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithAppend(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');

        $this->renderer->setColumn($column);
        $this->assertSame('\'col\' => $this->def()->append(\'aaa\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithAppendAndCompositePrimaryKey(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(true);

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def()->append(\'aaa\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
    public function shouldRenderProperlySimpleColumnWithAppendAndNoColumnPrimaryKey(): void
    {
        $column = $this->createMock(ColumnInterface::class);
        $column->method('getName')->willReturn('col');
        $column->method('getDefinition')->willReturn('def({renderLength})');
        $column->method('getAppend')->willReturn('aaa');
        $column->method('isColumnInPrimaryKey')->willReturn(false);

        $primaryKey = $this->createMock(PrimaryKeyInterface::class);
        $primaryKey->method('isComposite')->willReturn(false);

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def()->append(\'aaa\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
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

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def()->append(\'aaa\'),', $this->renderer->render('test'));
    }

    /**
     * @test
     */
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

        $this->renderer->setColumn($column);
        $this->renderer->setPrimaryKey($primaryKey);
        $this->assertSame('\'col\' => $this->def()->append(\'schema-append aaa\'),', $this->renderer->render('test'));
    }
}
