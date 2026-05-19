<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * 13th-month pay calculators (PD 851).
 *
 * DOLE's canonical formula: 13th-month = total basic salary earned in the
 * calendar year ÷ 12.
 */
final class ThirteenthMonth
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param float $totalBasicEarnings Total basic salary earned in the window.
     */
    public static function fromTotal(float $totalBasicEarnings): float
    {
        if (!is_finite($totalBasicEarnings) || $totalBasicEarnings < 0) {
            throw new \InvalidArgumentException('ThirteenthMonth::fromTotal: totalBasicEarnings must be a non-negative number');
        }
        return self::round2($totalBasicEarnings / 12);
    }

    /**
     * Convenience: fixed monthly salary, possibly partial year.
     *
     * @param float $monthlyBasicSalary
     * @param int   $monthsWorked       Default 12.
     */
    public static function fromMonthly(float $monthlyBasicSalary, int $monthsWorked = 12): float
    {
        if (!is_finite($monthlyBasicSalary) || $monthlyBasicSalary < 0) {
            throw new \InvalidArgumentException('ThirteenthMonth::fromMonthly: monthlyBasicSalary must be a non-negative number');
        }
        if ($monthsWorked < 0 || $monthsWorked > 12) {
            throw new \OutOfRangeException('ThirteenthMonth::fromMonthly: monthsWorked must be between 0 and 12');
        }
        return self::fromTotal($monthlyBasicSalary * $monthsWorked);
    }
}
