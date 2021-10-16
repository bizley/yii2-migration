<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\ComparatorInterface;
use bizley\migration\ExtractorInterface;
use bizley\migration\HistoryManagerInterface;
use bizley\migration\Inspector;
use bizley\migration\table\StructureBuilderInterface;
use bizley\migration\table\StructureChangeInterface;
use bizley\migration\table\StructureInterface;
use ErrorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/** @group inspector */
final class InspectorTest extends TestCase
{
    /** @var HistoryManagerInterface|MockObject */
    private $historyManager;

    /** @var ExtractorInterface|MockObject */
    private $extractor;

    /** @var StructureBuilderInterface|MockObject */
    private $builder;

    /** @var ComparatorInterface|MockObject */
    private $comparator;

    /** @var Inspector */
    private $inspector;

    protected function setUp(): void
    {
        $this->historyManager = $this->createMock(HistoryManagerInterface::class);
        $this->extractor = $this->createMock(ExtractorInterface::class);
        $this->builder = $this->createMock(StructureBuilderInterface::class);
        $this->comparator = $this->createMock(ComparatorInterface::class);
        $this->inspector = new Inspector(
            $this->historyManager,
            $this->extractor,
            $this->builder,
            $this->comparator
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function shouldReturnPendingBlueprintWhenNoHistory(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn([]);
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('table', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldReturnPendingBlueprintWhenAllHistorySkipped(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration' => 1]);
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            ['migration'],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('table', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldReturnPendingBlueprintWhenAllHistorySkippedAndMigrationNotTrimmed(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration\\' => 1]);
        $this->comparator->expects(self::never())->method('compare');
        $this->extractor->expects(self::never())->method('extract');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            ['migration'],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('table', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldReturnPendingBlueprintWhenNoChangesGathered(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration' => 1]);
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('table', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStartFromScratchWhenNoTableInHistory(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration1' => 1]);
        $this->extractor->method('getChanges')->willReturn(
            ['no-test' => [$this->createMock(StructureChangeInterface::class)]]
        );
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('test', $blueprint->getTableName());
    }


    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStartFromScratchWhenMethodIsDropTable(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(
            [
                'migration1' => 1,
                'migration2' => 2
            ]
        );
        $structureChange = $this->createMock(StructureChangeInterface::class);
        $structureChange->method('getMethod')->willReturn('dropTable');
        $this->extractor->method('getChanges')->willReturn(['test' => [$structureChange]]);
        $this->extractor->expects(self::once())->method('extract');
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('test', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldReturnNonPendingBlueprintWhenMethodIsCreateTableAndStructuresAreSame(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(
            [
                'migration1' => 1,
                'migration2' => 2
            ]
        );
        $structureChange = $this->createMock(StructureChangeInterface::class);
        $structureChange->method('getMethod')->willReturn('createTable');
        $this->extractor->method('getChanges')->willReturn(['test' => [$structureChange]]);
        $this->extractor->expects(self::once())->method('extract');
        $this->comparator->expects(self::once())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertFalse($blueprint->isPending());
        self::assertSame('test', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStartFromScratchWhenMethodIsRenameAndDropTable(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(
            [
                'migration1' => 1,
                'migration2' => 2
            ]
        );
        $structureChangeRename = $this->createMock(StructureChangeInterface::class);
        $structureChangeRename->method('getMethod')->willReturn('renameTable');
        $structureChangeRename->method('getValue')->willReturn('renamed-table');
        $structureChangeDrop = $this->createMock(StructureChangeInterface::class);
        $structureChangeDrop->method('getMethod')->willReturn('dropTable');
        $this->extractor->method('getChanges')->willReturn(
            [
                'test' => [$structureChangeRename],
                'renamed-test' => [$structureChangeDrop]
            ]
        );
        $this->extractor->expects(self::exactly(2))->method('extract');
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertTrue($blueprint->isPending());
        self::assertSame('test', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldGatherChangesAndPrepareBlueprint(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration1' => 1]);
        $structureChange = $this->createMock(StructureChangeInterface::class);
        $structureChange->method('getMethod')->willReturn('addColumn');
        $this->extractor->method('getChanges')->willReturn(['test' => [$structureChange]]);
        $this->extractor->expects(self::once())->method('extract');
        $this->comparator->expects(self::once())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $blueprint = $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
        self::assertFalse($blueprint->isPending());
        self::assertSame('test', $blueprint->getTableName());
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldContinueInspectingOnSomeHistorySkipped(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(['migration' => 1, 'migration2' => 2, 'migration3' => 3]);
        $this->comparator->expects(self::never())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('table');
        $this->extractor->expects(self::exactly(2))->method('extract');
        $this->inspector->prepareBlueprint(
            $structure,
            false,
            ['migration2'],
            [],
            null,
            null
        );
    }

    /**
     * @test
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldAllowToGatherMoreChangesIfMethodsAreNotStoppingIt(): void
    {
        $this->historyManager->method('fetchHistory')->willReturn(
            [
                'migration1' => 1,
                'migration2' => 2
            ]
        );
        $structureChange = $this->createMock(StructureChangeInterface::class);
        $structureChange->method('getMethod')->willReturn('someMethod');
        $this->extractor->method('getChanges')->willReturn(['test' => [$structureChange]]);
        $this->extractor->expects(self::exactly(2))->method('extract');
        $this->comparator->expects(self::once())->method('compare');
        $structure = $this->createMock(StructureInterface::class);
        $structure->method('getName')->willReturn('test');
        $this->inspector->prepareBlueprint(
            $structure,
            false,
            [],
            [],
            null,
            null
        );
    }
}
