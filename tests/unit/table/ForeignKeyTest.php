<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\ForeignKey;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group foreignkey
 */
final class ForeignKeyTest extends TestCase
{
    /** @var ForeignKey */
    private $fk;

    protected function setUp(): void
    {
        $this->fk = new ForeignKey();
    }

    public function providerForName(): array
    {
        return [
            'null' => [null, 'fk-tab-a-b'],
            'empty' => ['', 'fk-tab-a-b'],
            'numeric' => ['123', 'fk-tab-a-b'],
            'non-empty' => ['proper-fk', 'proper-fk'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForName
     */
    public function shouldReturnName(?string $input, string $output): void
    {
        $this->fk->setTableName('tab');
        $this->fk->setColumns(['a', 'b']);
        $this->fk->setName($input);

        self::assertSame($output, $this->fk->getName());
    }

    public function providerForFKConstraints(): array
    {
        return [
            'no action' => ['no action', 'no action'],
            'NO ACTION' => ['NO ACTION', 'NO ACTION'],
            'no_action' => ['no_action', 'no_action'],
            'NO_ACTION' => ['NO_ACTION', 'NO_ACTION'],
            'noaction' => ['noaction', 'NO_ACTION'],
            'NOACTION' => ['NOACTION', 'NO_ACTION'],
            'setnull' => ['setnull', 'SET_NULL'],
            'SETNULL' => ['SETNULL', 'SET_NULL'],
            'setdefault' => ['setdefault', 'SET_DEFAULT'],
            'SETDEFAULT' => ['SETDEFAULT', 'SET_DEFAULT'],
            'cascade' => ['cascade', 'cascade'],
            'CASCADE' => ['CASCADE', 'CASCADE'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForFKConstraints
     */
    public function shouldReturnProperConstraint(?string $input, string $output): void
    {
        $this->fk->setOnDelete($input);
        $this->fk->setOnUpdate($input);

        self::assertSame($output, $this->fk->getOnDelete());
        self::assertSame($output, $this->fk->getOnUpdate());
    }
}
