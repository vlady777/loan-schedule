<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
class MoneyTest extends TestCase
{
    #[
        TestWith([0, 0.0]),
        TestWith([1, 0.0001]),
        TestWith([392, 0.0392]),
        TestWith([10000, 1.0]),
        TestWith([392.0, 0.0392]),
    ]
    public function testBasisPointsToValue(int|float $basisPoints, float $expected): void
    {
        self::assertSame($expected, Money::basisPointsToValue($basisPoints));
    }

    #[
        TestWith([0, 0.0]),
        TestWith([12, 1.0]),
        TestWith([120, 10.0]),
        TestWith([300, 25.0]),
        TestWith([600.0, 50.0]),
    ]
    public function testYearlyToMonthly(int|float $value, float $expected): void
    {
        self::assertSame($expected, Money::yearlyToMonthly($value));
    }

    #[
        TestWith([0, 0]),
        TestWith([1, 1]),
        TestWith([1234, 1234]),
        TestWith([0.0, 0]),
        TestWith([1.0, 1]),
        TestWith([1.4, 1]),
        TestWith([1.5, 2]),
        TestWith([1234.56, 1235]),
    ]
    public function testRoundCents(int|float $value, int $expected): void
    {
        self::assertSame($expected, Money::roundCents($value));
    }
}
