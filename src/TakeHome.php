<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * Pre-tax net take-home: gross monthly salary minus mandatory SSS / PhilHealth /
 * Pag-IBIG employee contributions.
 *
 * **DOES NOT INCLUDE BIR WITHHOLDING TAX.** WT requires taxable-income calc
 * (gross minus non-taxable allowances minus mandatories), per-period derived
 * tables, de minimis benefits handling, and year-end annualization. WT is
 * planned for v0.2 after user feedback / accuracy validation.
 */
final class TakeHome
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int} $opts
     * @return array{gross: float, sss: array, philHealth: array, pagIbig: array, totalDeductions: float, net: float}
     */
    public static function netTakeHome(float $monthlySalary, array $opts = []): array
    {
        if (!is_finite($monthlySalary) || $monthlySalary < 0) {
            throw new \InvalidArgumentException('TakeHome::netTakeHome: monthlySalary must be a non-negative number');
        }

        $sss = Sss::contribution($monthlySalary, $opts);
        $philHealth = PhilHealth::contribution($monthlySalary, $opts);
        $pagIbig = PagIbig::contribution($monthlySalary, $opts);

        $totalDeductions = self::round2(
            $sss['employeeShare'] + $philHealth['employee'] + $pagIbig['employee']
        );
        $net = self::round2($monthlySalary - $totalDeductions);

        return [
            'gross' => $monthlySalary,
            'sss' => $sss,
            'philHealth' => $philHealth,
            'pagIbig' => $pagIbig,
            'totalDeductions' => $totalDeductions,
            'net' => $net,
        ];
    }
}
