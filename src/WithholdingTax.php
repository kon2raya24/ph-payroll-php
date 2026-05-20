<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * BIR withholding tax (monthly) — TRAIN Law, second phase of rate reductions
 * effective 2023-01-01 (BIR RR 11-2018).
 *
 * Operates on TAXABLE INCOME, not gross. Use TaxableIncome::monthly() to derive.
 */
final class WithholdingTax
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    private static function assertYearSupported(array $table, ?int $year): void
    {
        if ($year === null) return;
        if (!in_array($year, $table['_meta']['applies_to_years'], true)) {
            $years = implode(', ', $table['_meta']['applies_to_years']);
            throw new \OutOfRangeException("WithholdingTax: no BIR monthly WT table available for year {$year}. Available: {$years}. Pin to an older package version if needed.");
        }
    }

    /**
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float}
     */
    public static function monthly(float $taxableIncome, array $opts = []): array
    {
        if (!is_finite($taxableIncome) || $taxableIncome < 0) {
            throw new \InvalidArgumentException('WithholdingTax::monthly: taxableIncome must be a non-negative number');
        }

        $table = DataLoader::load('bir-wt-monthly-train-2023');
        self::assertYearSupported($table, $opts['year'] ?? null);

        $chosen = $table['brackets'][0];
        foreach ($table['brackets'] as $b) {
            if ($taxableIncome > $b['over']) {
                $chosen = $b;
            } else {
                break;
            }
        }

        $wt = self::round2($chosen['base'] + $chosen['rate_over_min'] * ($taxableIncome - $chosen['over']));

        return [
            'wt' => $wt,
            'bracket' => (int) $chosen['bracket'],
            'marginalRate' => (float) $chosen['rate_over_min'],
        ];
    }
}
