<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * BIR withholding tax (per-period) — TRAIN Law, second phase of rate reductions
 * effective 2023-01-01 (BIR RR 11-2018).
 *
 * Operates on TAXABLE INCOME, not gross. Use TaxableIncome::monthly() to derive
 * the monthly case; for other periods, callers compute taxable income themselves
 * (SSS/PhilHealth/Pag-IBIG are monthly by rule, so per-period mandatories is an
 * employer policy choice).
 */
final class WithholdingTax
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /** @param array<string, mixed> $table */
    private static function assertYearSupported(array $table, ?int $year, string $fnName): void
    {
        if ($year === null) return;
        if (!in_array($year, $table['_meta']['applies_to_years'], true)) {
            $years = implode(', ', $table['_meta']['applies_to_years']);
            $period = $table['_meta']['period'];
            throw new \OutOfRangeException("{$fnName}: no BIR {$period} WT table available for year {$year}. Available: {$years}. Pin to an older package version if needed.");
        }
    }

    /**
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float, period: string}
     */
    private static function compute(string $dataName, float $taxableIncome, array $opts, string $fnName): array
    {
        if (!is_finite($taxableIncome) || $taxableIncome < 0) {
            throw new \InvalidArgumentException("{$fnName}: taxableIncome must be a non-negative number");
        }

        $table = DataLoader::load($dataName);
        self::assertYearSupported($table, $opts['year'] ?? null, $fnName);

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
            'period' => (string) $table['_meta']['period'],
        ];
    }

    /**
     * Monthly BIR WT (RR 11-2018 monthly table).
     *
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float, period: string}
     */
    public static function monthly(float $taxableIncome, array $opts = []): array
    {
        return self::compute('bir-wt-monthly-train-2023', $taxableIncome, $opts, 'WithholdingTax::monthly');
    }

    /**
     * Semi-monthly BIR WT (RR 11-2018 semi-monthly table).
     *
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float, period: string}
     */
    public static function semiMonthly(float $taxableIncome, array $opts = []): array
    {
        return self::compute('bir-wt-semi-monthly-train-2023', $taxableIncome, $opts, 'WithholdingTax::semiMonthly');
    }

    /**
     * Weekly BIR WT (RR 11-2018 weekly table).
     *
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float, period: string}
     */
    public static function weekly(float $taxableIncome, array $opts = []): array
    {
        return self::compute('bir-wt-weekly-train-2023', $taxableIncome, $opts, 'WithholdingTax::weekly');
    }

    /**
     * Daily BIR WT (RR 11-2018 daily table).
     *
     * @param array{year?: int} $opts
     * @return array{wt: float, bracket: int, marginalRate: float, period: string}
     */
    public static function daily(float $taxableIncome, array $opts = []): array
    {
        return self::compute('bir-wt-daily-train-2023', $taxableIncome, $opts, 'WithholdingTax::daily');
    }
}
