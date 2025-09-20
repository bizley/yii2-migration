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
            'no_action' => ['no_action', 'NO ACTION'],
            'NO_ACTION' => ['NO_ACTION', 'NO ACTION'],
            'noaction' => ['noaction', 'NO ACTION'],
            'NOACTION' => ['NOACTION', 'NO ACTION'],
            'setnull' => ['setnull', 'SET NULL'],
            'SETNULL' => ['SETNULL', 'SET NULL'],
            'SET_NULL' => ['SET_NULL', 'SET NULL'],
            'setdefault' => ['setdefault', 'SET DEFAULT'],
            'SETDEFAULT' => ['SETDEFAULT', 'SET DEFAULT'],
            'SET_DEFAULT' => ['SET_DEFAULT', 'SET DEFAULT'],
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
