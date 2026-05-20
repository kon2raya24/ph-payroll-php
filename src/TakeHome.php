<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * Net take-home: gross monthly salary minus mandatory SSS / PhilHealth / Pag-IBIG
 * employee contributions, and (optionally, v0.2+) BIR withholding tax.
 *
 * By default returns pre-tax (v0.1 shape). Pass `includeWT => true` to also compute
 * monthly WT and netAfterTax.
 */
final class TakeHome
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int, includeWT?: bool, nonTaxableAllowances?: float} $opts
     * @return array{gross: float, sss: array, philHealth: array, pagIbig: array, totalDeductions: float, net: float, taxableIncome?: float, withholdingTax?: float, netAfterTax?: float}
     */
    public static function netTakeHome(float $monthlySalary, array $opts = []): array
    {
        if (!is_finite($monthlySalary) || $monthlySalary < 0) {
            throw new \InvalidArgumentException('TakeHome::netTakeHome: monthlySalary must be a non-negative number');
        }

        $contribOpts = isset($opts['year']) ? ['year' => $opts['year']] : [];

        $sss = Sss::contribution($monthlySalary, $contribOpts);
        $philHealth = PhilHealth::contribution($monthlySalary, $contribOpts);
        $pagIbig = PagIbig::contribution($monthlySalary, $contribOpts);

        $totalDeductions = self::round2(
            $sss['employeeShare'] + $philHealth['employee'] + $pagIbig['employee']
        );
        $net = self::round2($monthlySalary - $totalDeductions);

        $result = [
            'gross' => $monthlySalary,
            'sss' => $sss,
            'philHealth' => $philHealth,
            'pagIbig' => $pagIbig,
            'totalDeductions' => $totalDeductions,
            'net' => $net,
        ];

        if (!empty($opts['includeWT'])) {
            $tiOpts = $contribOpts + [
                'mandatoryDeductions' => $totalDeductions,
                'nonTaxableAllowances' => (float) ($opts['nonTaxableAllowances'] ?? 0),
            ];
            $taxableIncome = TaxableIncome::monthly($monthlySalary, $tiOpts);
            $wt = WithholdingTax::monthly($taxableIncome, $contribOpts);
            $result['taxableIncome'] = $taxableIncome;
            $result['withholdingTax'] = $wt['wt'];
            $result['netAfterTax'] = self::round2($net - $wt['wt']);
        }

        return $result;
    }
}
