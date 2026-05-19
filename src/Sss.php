<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * Compute SSS monthly contribution for an employed member.
 * Source: SSS Circular 2024-006 (effective 2025-01-01, applies to 2025 and 2026).
 *
 * Return shape:
 *   [
 *     'msc' => int,
 *     'regular' => ['employee' => float, 'employer' => float, 'total' => float],
 *     'mpf'     => ['employee' => float, 'employer' => float, 'total' => float],
 *     'ec' => float,
 *     'employeeShare' => float,
 *     'employerShare' => float,
 *     'total' => float,
 *   ]
 */
final class Sss
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int} $opts
     * @return array{msc: int, regular: array{employee: float, employer: float, total: float}, mpf: array{employee: float, employer: float, total: float}, ec: float, employeeShare: float, employerShare: float, total: float}
     */
    public static function contribution(float $monthlyCompensation, array $opts = []): array
    {
        if (!is_finite($monthlyCompensation) || $monthlyCompensation < 0) {
            throw new \InvalidArgumentException('Sss::contribution: monthlyCompensation must be a non-negative number');
        }

        $table = DataLoader::load('sss-table-2025');

        if (isset($opts['year']) && !in_array($opts['year'], $table['_meta']['applies_to_years'], true)) {
            $years = implode(', ', $table['_meta']['applies_to_years']);
            throw new \OutOfRangeException("Sss::contribution: no SSS table available for year {$opts['year']}. Available: {$years}. Pin to an older package version if needed.");
        }

        $min = $table['msc']['min'];
        $max = $table['msc']['max'];
        $increment = $table['msc']['increment'];
        $regularCap = $table['msc']['regular_ss_cap'];

        if ($monthlyCompensation < $min) {
            $msc = $min;
        } elseif ($monthlyCompensation >= $max) {
            $msc = $max;
        } else {
            $msc = (int) (round($monthlyCompensation / $increment) * $increment);
        }

        $regularMsc = min($msc, $regularCap);
        $mpfMsc = max(0, $msc - $regularCap);

        $empPct = $table['rate']['employee_percent'];
        $erPct = $table['rate']['employer_percent'];

        $regular = [
            'employee' => self::round2($regularMsc * $empPct),
            'employer' => self::round2($regularMsc * $erPct),
            'total'    => self::round2($regularMsc * ($empPct + $erPct)),
        ];

        $mpf = [
            'employee' => self::round2($mpfMsc * $empPct),
            'employer' => self::round2($mpfMsc * $erPct),
            'total'    => self::round2($mpfMsc * ($empPct + $erPct)),
        ];

        $ec = $msc <= $table['ec']['low_msc_threshold_inclusive']
            ? (float) $table['ec']['low_amount']
            : (float) $table['ec']['high_amount'];

        $employeeShare = self::round2($regular['employee'] + $mpf['employee']);
        $employerShare = self::round2($regular['employer'] + $mpf['employer'] + $ec);
        $total = self::round2($employeeShare + $employerShare);

        return [
            'msc' => $msc,
            'regular' => $regular,
            'mpf' => $mpf,
            'ec' => $ec,
            'employeeShare' => $employeeShare,
            'employerShare' => $employerShare,
            'total' => $total,
        ];
    }
}
