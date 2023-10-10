<?php

declare(strict_types=1);

namespace App\Helper;

class Money
{
    public static function basisPointsToValue(int|float $basisPoints): float
    {
        return $basisPoints / 1e4;
    }

    public static function yearlyToMonthly(int|float $value): float
    {
        return $value / 12;
    }

    public static function roundCents(int|float $cents): int
    {
        return (int) round($cents);
    }
}
