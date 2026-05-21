<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

/**
 * BIR de minimis benefit cap helpers (RR 29-2025, effective 2025-12-22).
 *
 * Caps per BIR rule are per-category — they cannot offset each other. The `nonTaxable`
 * total returned is directly usable as `nonTaxableAllowances` in TaxableIncome::monthly()
 * or TakeHome::netTakeHome() with `includeWT => true`.
 */
final class DeMinimis
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array<int, array{category: string, given: float, cap: float, nonTaxable: float, taxableExcess: float}> $breakdown (passed by reference)
     * @return array{nt: float, te: float}
     */
    private static function checkCap(string $category, ?float $given, float $cap, array &$breakdown): array
    {
        if ($given === null) {
            return ['nt' => 0.0, 'te' => 0.0];
        }
        if (!is_finite($given) || $given < 0) {
            throw new \InvalidArgumentException("DeMinimis: {$category} must be a non-negative number");
        }
        $nt = self::round2(min($given, $cap));
        $te = self::round2(max(0.0, $given - $cap));
        $breakdown[] = [
            'category' => $category,
            'given' => self::round2($given),
            'cap' => $cap,
            'nonTaxable' => $nt,
            'taxableExcess' => $te,
        ];
        return ['nt' => $nt, 'te' => $te];
    }

    /**
     * Apply MONTHLY de minimis caps (rice ₱2,500, laundry ₱400, medical-dependents ₱333.33).
     *
     * @param array{riceSubsidy?: float, laundryAllowance?: float, medicalCashDependents?: float} $input
     * @return array{nonTaxable: float, taxableExcess: float, breakdown: array, _source: array{effective_from: string, source: string}}
     */
    public static function monthly(array $input): array
    {
        $caps = DataLoader::load('bir-de-minimis-2025');
        $m = $caps['monthly_caps'];
        $breakdown = [];
        $nt = 0.0;
        $te = 0.0;

        foreach ([
            ['riceSubsidy', $input['riceSubsidy'] ?? null, $m['rice_subsidy']['cap_php']],
            ['laundryAllowance', $input['laundryAllowance'] ?? null, $m['laundry_allowance']['cap_php']],
            ['medicalCashDependents', $input['medicalCashDependents'] ?? null, $m['medical_cash_allowance_dependents']['cap_php']],
        ] as [$key, $given, $cap]) {
            $r = self::checkCap($key, $given === null ? null : (float) $given, (float) $cap, $breakdown);
            $nt += $r['nt'];
            $te += $r['te'];
        }

        return [
            'nonTaxable' => self::round2($nt),
            'taxableExcess' => self::round2($te),
            'breakdown' => $breakdown,
            '_source' => [
                'effective_from' => $caps['_meta']['effective_from'],
                'source' => $caps['_meta']['source'],
            ],
        ];
    }

    /**
     * Apply ANNUAL de minimis caps.
     *
     * @param array{uniformClothing?: float, medicalAssistance?: float, employeeAchievementAwards?: float, giftsChristmasAnniversary?: float, cbaProductivityIncentives?: float, monetizedUnusedLeavePrivate?: array{days: float, dailyRate: float}} $input
     * @return array{nonTaxable: float, taxableExcess: float, breakdown: array, _source: array{effective_from: string, source: string}}
     */
    public static function annual(array $input): array
    {
        $caps = DataLoader::load('bir-de-minimis-2025');
        $a = $caps['annual_caps'];
        $breakdown = [];
        $nt = 0.0;
        $te = 0.0;

        foreach ([
            ['uniformClothing', $input['uniformClothing'] ?? null, $a['uniform_clothing']['cap_php']],
            ['medicalAssistance', $input['medicalAssistance'] ?? null, $a['medical_assistance']['cap_php']],
            ['employeeAchievementAwards', $input['employeeAchievementAwards'] ?? null, $a['employee_achievement_awards']['cap_php']],
            ['giftsChristmasAnniversary', $input['giftsChristmasAnniversary'] ?? null, $a['gifts_christmas_anniversary']['cap_php']],
            ['cbaProductivityIncentives', $input['cbaProductivityIncentives'] ?? null, $a['cba_productivity_incentives']['cap_php']],
        ] as [$key, $given, $cap]) {
            $r = self::checkCap($key, $given === null ? null : (float) $given, (float) $cap, $breakdown);
            $nt += $r['nt'];
            $te += $r['te'];
        }

        if (isset($input['monetizedUnusedLeavePrivate'])) {
            $leave = $input['monetizedUnusedLeavePrivate'];
            $days = (float) ($leave['days'] ?? 0);
            $dailyRate = (float) ($leave['dailyRate'] ?? 0);
            if (!is_finite($days) || $days < 0) {
                throw new \InvalidArgumentException('DeMinimis::annual: monetizedUnusedLeavePrivate.days must be a non-negative number');
            }
            if (!is_finite($dailyRate) || $dailyRate < 0) {
                throw new \InvalidArgumentException('DeMinimis::annual: monetizedUnusedLeavePrivate.dailyRate must be a non-negative number');
            }
            $dayCap = (float) $a['monetized_unused_vl_private']['cap_days'];
            $totalGiven = self::round2($days * $dailyRate);
            $cap = self::round2($dayCap * $dailyRate);
            $ntLeave = self::round2(min($totalGiven, $cap));
            $teLeave = self::round2(max(0.0, $totalGiven - $cap));
            $breakdown[] = [
                'category' => 'monetizedUnusedLeavePrivate',
                'given' => $totalGiven,
                'cap' => $cap,
                'nonTaxable' => $ntLeave,
                'taxableExcess' => $teLeave,
            ];
            $nt += $ntLeave;
            $te += $teLeave;
        }

        return [
            'nonTaxable' => self::round2($nt),
            'taxableExcess' => self::round2($te),
            'breakdown' => $breakdown,
            '_source' => [
                'effective_from' => $caps['_meta']['effective_from'],
                'source' => $caps['_meta']['source'],
            ],
        ];
    }
}
