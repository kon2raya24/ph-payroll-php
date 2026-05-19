<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

final class PagIbig
{
    private static function round2(float $n): float
    {
        return round($n, 2);
    }

    /**
     * @param array{year?: int} $opts
     * @return array{mfs: float, employee: float, employer: float, total: float}
     */
    public static function contribution(float $monthlySalary, array $opts = []): array
    {
        if (!is_finite($monthlySalary) || $monthlySalary < 0) {
            throw new \InvalidArgumentException('PagIbig::contribution: monthlySalary must be a non-negative number');
        }

        $table = DataLoader::load('pagibig-rate-2024');

        if (isset($opts['year']) && !in_array($opts['year'], $table['_meta']['applies_to_years'], true)) {
            $years = implode(', ', $table['_meta']['applies_to_years']);
            throw new \OutOfRangeException("PagIbig::contribution: no Pag-IBIG table available for year {$opts['year']}. Available: {$years}.");
        }

        $mfs = min($monthlySalary, (float) $table['msc']['cap']);

        $bracket = $table['brackets'][count($table['brackets']) - 1];
        foreach ($table['brackets'] as $b) {
            if ($b['mfs_upper_inclusive'] !== null && $mfs <= $b['mfs_upper_inclusive']) {
                $bracket = $b;
                break;
            }
        }

        $employee = self::round2($mfs * $bracket['employee_percent']);
        $employer = self::round2($mfs * $bracket['employer_percent']);

        return [
            'mfs' => $mfs,
            'employee' => $employee,
            'employer' => $employer,
            'total' => self::round2($employee + $employer),
        ];
    }
}
