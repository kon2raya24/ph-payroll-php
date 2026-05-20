<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * Derive monthly taxable income from gross compensation.
 *
 *   taxableIncome = gross − mandatoryDeductions − nonTaxableAllowances
 *
 * If `mandatoryDeductions` is omitted, it is auto-computed as the sum of SSS,
 * PhilHealth, and Pag-IBIG employee shares for the given gross.
 *
 * Not handled here:
 *   - 13th-month + bonus ₱90k annual exemption (annual concern, not monthly).
 *   - De minimis benefit caps — caller passes only the non-taxable portion.
 */
final class TaxableIncome
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int, mandatoryDeductions?: float, nonTaxableAllowances?: float} $opts
     */
    public static function monthly(float $gross, array $opts = []): float
    {
        if (!is_finite($gross) || $gross < 0) {
            throw new \InvalidArgumentException('TaxableIncome::monthly: gross must be a non-negative number');
        }

        if (isset($opts['mandatoryDeductions'])) {
            $mandatory = (float) $opts['mandatoryDeductions'];
        } else {
            $year = isset($opts['year']) ? ['year' => $opts['year']] : [];
            $sss = Sss::contribution($gross, $year);
            $ph = PhilHealth::contribution($gross, $year);
            $pi = PagIbig::contribution($gross, $year);
            $mandatory = $sss['employeeShare'] + $ph['employee'] + $pi['employee'];
        }
        if (!is_finite($mandatory) || $mandatory < 0) {
            throw new \InvalidArgumentException('TaxableIncome::monthly: mandatoryDeductions must be a non-negative number');
        }

        $nta = (float) ($opts['nonTaxableAllowances'] ?? 0);
        if (!is_finite($nta) || $nta < 0) {
            throw new \InvalidArgumentException('TaxableIncome::monthly: nonTaxableAllowances must be a non-negative number');
        }

        return self::round2(max(0.0, $gross - $mandatory - $nta));
    }
}
