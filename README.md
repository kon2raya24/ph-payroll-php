# phdevutils/payroll

[![Packagist version](https://img.shields.io/packagist/v/phdevutils/payroll?label=Packagist&color=f28d1a&logo=packagist&logoColor=white)](https://packagist.org/packages/phdevutils/payroll)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/kon2raya24/ph-payroll/blob/main/LICENSE)
[![Made in PH](https://img.shields.io/badge/made%20in-🇵🇭%20Philippines-0038A8)](https://github.com/kon2raya24)

Filipino payroll calculators for PHP — SSS, PhilHealth, Pag-IBIG monthly contributions, 13th-month pay, and pre-tax net take-home. Tables versioned by effective date.

## ⚠️ READ FIRST

This package handles **real money math**. Wrong outputs cause underpayment / overpayment in actual payroll runs. Verify outputs against official agency calculators before production use. Pin your dependency version — tables change every few years. **BIR withholding tax is NOT included in v0.1** (deferred to v0.2).

Full disclaimer: [project README](https://github.com/kon2raya24/ph-payroll#-read-first--accuracy-disclaimer).

## Install

```bash
composer require phdevutils/payroll
```

Requires PHP 8.2+.

## Quick start

```php
use PhDevUtils\Payroll\TakeHome;

$r = TakeHome::netTakeHome(30000);
// [
//   'gross' => 30000.0,
//   'sss' => ['msc' => 30000, 'employeeShare' => 1500.0, ...],
//   'philHealth' => ['total' => 1500.0, 'employee' => 750.0, 'employer' => 750.0],
//   'pagIbig' => ['mfs' => 10000.0, 'employee' => 200.0, 'employer' => 200.0, 'total' => 400.0],
//   'totalDeductions' => 2450.0,
//   'net' => 27550.0,
// ]
```

## API Reference

### `Sss::contribution(float $monthlyCompensation, array $opts = []): array`

Source: **SSS Circular 2024-006** (effective 2025-01-01, applies to 2025–2026).

Return shape:
```php
[
  'msc' => int,                                                         // ₱500 increments, clamped 5,000–35,000
  'regular' => ['employee' => float, 'employer' => float, 'total' => float],  // MSC capped at 20,000
  'mpf'     => ['employee' => float, 'employer' => float, 'total' => float],  // MySSS Pension Booster — above 20,000
  'ec' => float,                                                        // Employees' Compensation (employer-paid)
  'employeeShare' => float,
  'employerShare' => float,
  'total' => float,
]
```

**Math:** Total rate 15% = 10% employer + 5% employee. EC ₱10 (MSC ≤ 14,500) or ₱30 (MSC ≥ 15,000), employer-paid.

```php
use PhDevUtils\Payroll\Sss;

$r = Sss::contribution(20000);
// $r['msc'] === 20000
// $r['employeeShare'] === 1000.0, $r['employerShare'] === 2030.0

$r = Sss::contribution(30000);
// MPF kicks in: $r['regular']['total'] === 3000.0, $r['mpf']['total'] === 1500.0
// $r['employeeShare'] === 1500.0

Sss::contribution(50000)['msc'];   // 35000 (capped)
Sss::contribution(3000)['msc'];    // 5000 (floored)
```

---

### `PhilHealth::contribution(float $monthlySalary, array $opts = []): array`

Source: **RA 11223 (UHC Law)** — final 5% rate (no further increases scheduled).

Return shape: `['total' => float, 'employee' => float, 'employer' => float]`.

**Math:** 5% split equally. Floor ₱500 (salary ≤ ₱10k), ceiling ₱5,000 (salary ≥ ₱100k). Each share rounded to centavo; total = sum of remitted shares.

```php
use PhDevUtils\Payroll\PhilHealth;

PhilHealth::contribution(25000);    // ['total' => 1250.0, 'employee' => 625.0, 'employer' => 625.0]
PhilHealth::contribution(5000);     // floor: ['total' => 500.0, 'employee' => 250.0, 'employer' => 250.0]
PhilHealth::contribution(150000);   // ceiling: ['total' => 5000.0, 'employee' => 2500.0, 'employer' => 2500.0]
```

---

### `PagIbig::contribution(float $monthlySalary, array $opts = []): array`

Source: **HDMF Circular 460** (effective 2024-02-01). MFS ceiling: ₱10,000.

Return shape: `['mfs' => float, 'employee' => float, 'employer' => float, 'total' => float]`.

**Math:**
- MFS = min(monthlySalary, 10,000)
- If MFS ≤ ₱1,500: employee 1%, employer 2%
- If MFS > ₱1,500: employee 2%, employer 2%
- Max ₱200 each side at MFS = ₱10,000

Mandatory contribution only.

```php
use PhDevUtils\Payroll\PagIbig;

PagIbig::contribution(1000);     // low bracket: ['mfs' => 1000.0, 'employee' => 10.0, 'employer' => 20.0, 'total' => 30.0]
PagIbig::contribution(5000);     // high bracket: ['mfs' => 5000.0, 'employee' => 100.0, 'employer' => 100.0, 'total' => 200.0]
PagIbig::contribution(50000);    // capped: ['mfs' => 10000.0, 'employee' => 200.0, 'employer' => 200.0, 'total' => 400.0]
```

---

### `ThirteenthMonth::fromTotal(float $totalBasicEarnings): float`

Source: **PD 851**. Canonical DOLE formula: `total / 12`.

```php
use PhDevUtils\Payroll\ThirteenthMonth;

ThirteenthMonth::fromTotal(360000);   // 30000.0
ThirteenthMonth::fromTotal(180000);   // 15000.0
```

### `ThirteenthMonth::fromMonthly(float $monthlyBasicSalary, int $monthsWorked = 12): float`

Convenience for fixed-salary employees.

```php
ThirteenthMonth::fromMonthly(30000);       // 30000.0 (full year)
ThirteenthMonth::fromMonthly(30000, 6);    // 15000.0 (6 months prorated)
ThirteenthMonth::fromMonthly(30000, 1);    // 2500.0
```

**"Basic earnings" excludes** allowances, overtime, holiday pay, NSD, and other non-base benefits.

---

### `TakeHome::netTakeHome(float $monthlySalary, array $opts = []): array`

Pre-tax net take-home: gross minus mandatory SSS / PhilHealth / Pag-IBIG employee contributions.

Return shape:
```php
[
  'gross' => float,
  'sss' => array,         // full Sss::contribution result
  'philHealth' => array,  // full PhilHealth::contribution result
  'pagIbig' => array,     // full PagIbig::contribution result
  'totalDeductions' => float,
  'net' => float,
]
```

**Important:** Does **NOT** subtract BIR withholding tax. To get true take-home, subtract WT separately using a tax engine.

```php
use PhDevUtils\Payroll\TakeHome;

TakeHome::netTakeHome(30000)['net'];     // 27550.0
TakeHome::netTakeHome(8000)['net'];      // 7190.0
TakeHome::netTakeHome(150000)['net'];    // 145550.0
```

---

## Versioning & rate changes

Tables change. The package contract:

- Within a major+minor version (e.g. `0.1.*`), tables don't change.
- New tables ship in new minor versions (`0.2.0`).
- Pin your dependency to a specific minor to avoid silent changes mid-payroll-cycle.

File issues when circulars drop — patches ship quickly.

## License

MIT
