<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * BIR ₱90,000 annual exemption on 13th-month pay + other bonuses.
 *
 * Source: RA 10963 (TRAIN Law), Section 32(B)(7)(e). Unchanged as of RR 29-2025.
 *
 * The exemption is aggregate, not per-item. Excess over ₱90,000 is taxable in the
 * year of receipt at graduated rates.
 */
final class AnnualExemption
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * Apply the ₱90,000 annual exemption.
     *
     * @param array{thirteenthMonthPay: float, otherBonuses?: float} $input
     * @return array{totalReceived: float, exempt: float, taxableExcess: float, cap: float, _source: array{authority: string, cap_source: string}}
     */
    public static function ninetyK(array $input): array
    {
        $thirteenth = (float) ($input['thirteenthMonthPay'] ?? 0);
        $other = (float) ($input['otherBonuses'] ?? 0);

        if (!is_finite($thirteenth) || $thirteenth < 0) {
            throw new \InvalidArgumentException('AnnualExemption::ninetyK: thirteenthMonthPay must be a non-negative number');
        }
        if (!is_finite($other) || $other < 0) {
            throw new \InvalidArgumentException('AnnualExemption::ninetyK: otherBonuses must be a non-negative number');
        }

        $caps = DataLoader::load('bir-de-minimis-2025');
        $totalReceived = self::round2($thirteenth + $other);
        $cap = (float) $caps['annual_exemption_13th_month_and_bonuses']['cap_php'];
        $exempt = self::round2(min($totalReceived, $cap));
        $taxableExcess = self::round2(max(0.0, $totalReceived - $cap));

        return [
            'totalReceived' => $totalReceived,
            'exempt' => $exempt,
            'taxableExcess' => $taxableExcess,
            'cap' => $cap,
            '_source' => [
                'authority' => (string) $caps['annual_exemption_13th_month_and_bonuses']['source'],
                'cap_source' => (string) $caps['_meta']['source'],
            ],
        ];
    }
}
