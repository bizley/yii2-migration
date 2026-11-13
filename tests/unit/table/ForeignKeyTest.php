<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\ForeignKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('foreignkey')]
final class ForeignKeyTest extends TestCase
{
    /** @var ForeignKey */
    private $fk;

    protected function setUp(): void
    {
        $this->fk = new ForeignKey();
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function providerForName(): array
    {
        return [
            'null' => [null, 'fk-tab-a-b'],
            'empty' => ['', 'fk-tab-a-b'],
            'numeric' => ['123', 'fk-tab-a-b'],
            'non-empty' => ['proper-fk', 'proper-fk'],
        ];
    }

    #[DataProvider('providerForName')]
    #[Test]
    public function shouldReturnName(?string $input, string $output): void
    {
        $this->fk->setTableName('tab');
        $this->fk->setColumns(['a', 'b']);
        $this->fk->setName($input);

        self::assertSame($output, $this->fk->getName());
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function providerForFKConstraints(): array
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

    #[DataProvider('providerForFKConstraints')]
    #[Test]
    public function shouldReturnProperConstraint(?string $input, string $output): void
    {
        $this->fk->setOnDelete($input);
        $this->fk->setOnUpdate($input);

        self::assertSame($output, $this->fk->getOnDelete());
        self::assertSame($output, $this->fk->getOnUpdate());
    }
}
