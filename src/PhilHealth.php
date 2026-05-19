<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

final class PhilHealth
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int} $opts
     * @return array{total: float, employee: float, employer: float}
     */
    public static function contribution(float $monthlySalary, array $opts = []): array
    {
        if (!is_finite($monthlySalary) || $monthlySalary < 0) {
            throw new \InvalidArgumentException('PhilHealth::contribution: monthlySalary must be a non-negative number');
        }

        $table = DataLoader::load('philhealth-rate-2026');

        if (isset($opts['year']) && !in_array($opts['year'], $table['_meta']['applies_to_years'], true)) {
            $years = implode(', ', $table['_meta']['applies_to_years']);
            throw new \OutOfRangeException("PhilHealth::contribution: no PhilHealth table available for year {$opts['year']}. Available: {$years}.");
        }

        if ($monthlySalary <= $table['floor']['salary_threshold_inclusive']) {
            $total = (float) $table['floor']['monthly_premium'];
        } elseif ($monthlySalary >= $table['ceiling']['salary_threshold_inclusive']) {
            $total = (float) $table['ceiling']['monthly_premium'];
        } else {
            $total = self::round2($monthlySalary * $table['rate']['total_percent']);
        }

        $each = self::round2($total / 2);
        return [
            'total' => self::round2($each * 2),
            'employee' => $each,
            'employer' => $each,
        ];
    }
}
