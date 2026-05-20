# phdevutils/payroll

[![Packagist version](https://img.shields.io/packagist/v/phdevutils/payroll?label=Packagist&color=f28d1a&logo=packagist&logoColor=white)](https://packagist.org/packages/phdevutils/payroll)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/kon2raya24/ph-payroll/blob/main/LICENSE)
[![Made in PH](https://img.shields.io/badge/made%20in-🇵🇭%20Philippines-0038A8)](https://github.com/kon2raya24)

Filipino payroll calculators for PHP — SSS, PhilHealth, Pag-IBIG monthly contributions, 13th-month pay, BIR monthly withholding tax (TRAIN), and pre-tax / post-tax net take-home. Tables versioned by effective date.

## ⚠️ READ FIRST

This package handles **real money math**. Wrong outputs cause underpayment / overpayment in actual payroll runs. Verify outputs against official agency calculators before production use. Pin your dependency version — tables change every few years. **BIR WT in v0.2 is monthly-only** and operates on **taxable income**, not raw gross. De minimis caps and the ₱90k 13th-month annual exemption are caller-handled in v0.2.

Full disclaimer: [project README](https://github.com/kon2raya24/ph-payroll#-read-first--accuracy-disclaimer).

## Install

```bash
composer require phdevutils/payroll
```

Requires PHP 8.2+.

## Quick start

```php
use PhDevUtils\Payroll\TakeHome;

// Pre-tax (v0.1 shape — unchanged):
$r = TakeHome::netTakeHome(30000);
// [
//   'gross' => 30000.0,
//   'sss' => ['msc' => 30000, 'employeeShare' => 1500.0, ...],
//   'philHealth' => ['total' => 1500.0, 'employee' => 750.0, 'employer' => 750.0],
//   'pagIbig' => ['mfs' => 10000.0, 'employee' => 200.0, 'employer' => 200.0, 'total' => 400.0],
//   'totalDeductions' => 2450.0,
//   'net' => 27550.0,
// ]

// With BIR monthly WT (v0.2 opt-in):
$r = TakeHome::netTakeHome(30000, ['includeWT' => true]);
// adds: 'taxableIncome' => 27550.0, 'withholdingTax' => 1007.55, 'netAfterTax' => 26542.45
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

### `WithholdingTax::monthly(float $taxableIncome, array $opts = []): array` *(v0.2+)*

Source: **BIR RR 11-2018** monthly table (TRAIN Law, second-phase rates effective 2023-01-01).

Return shape: `['wt' => float, 'bracket' => int (1-6), 'marginalRate' => float]`.

**Input is taxable income, not gross.** Use `TaxableIncome::monthly` to derive.

**BIR monthly brackets (TRAIN 2023+):**

| Bracket | Monthly taxable income | Tax |
| --- | --- | --- |
| 1 | ≤ ₱20,833 | 0 |
| 2 | ₱20,833 – ₱33,333 | 15% of excess over ₱20,833 |
| 3 | ₱33,333 – ₱66,667 | ₱1,875 + 20% of excess over ₱33,333 |
| 4 | ₱66,667 – ₱166,667 | ₱8,541.80 + 25% of excess over ₱66,667 |
| 5 | ₱166,667 – ₱666,667 | ₱33,541.80 + 30% of excess over ₱166,667 |
| 6 | > ₱666,667 | ₱183,541.80 + 35% of excess over ₱666,667 |

```php
use PhDevUtils\Payroll\WithholdingTax;

WithholdingTax::monthly(15000);    // ['wt' => 0.0,        'bracket' => 1, 'marginalRate' => 0.0]
WithholdingTax::monthly(25000);    // ['wt' => 625.05,     'bracket' => 2, 'marginalRate' => 0.15]
WithholdingTax::monthly(50000);    // ['wt' => 5208.4,     'bracket' => 3, 'marginalRate' => 0.20]
WithholdingTax::monthly(100000);   // ['wt' => 16875.05,   'bracket' => 4, 'marginalRate' => 0.25]
WithholdingTax::monthly(200000);   // ['wt' => 43541.7,    'bracket' => 5, 'marginalRate' => 0.30]
WithholdingTax::monthly(1000000);  // ['wt' => 300208.35,  'bracket' => 6, 'marginalRate' => 0.35]
```

---

### `TaxableIncome::monthly(float $gross, array $opts = []): float` *(v0.2+)*

Derive monthly taxable income from gross. Formula:

```
taxableIncome = gross − mandatoryDeductions − nonTaxableAllowances
```

`$opts` keys: `year`, `mandatoryDeductions` (override; default: auto-computed from gross via SSS+PhilHealth+Pag-IBIG), `nonTaxableAllowances` (default: 0).

**Caller-handled** (not done here):
- **De minimis caps** — pass only the non-taxable portion. Rice ₱2,000/mo, uniform ₱6,000/yr, medical ₱10,000/yr (employee), etc.
- **₱90,000 annual exemption** for 13th-month + bonuses — applies at year level.

```php
use PhDevUtils\Payroll\TaxableIncome;

TaxableIncome::monthly(30000);                                     // 27550.0
TaxableIncome::monthly(30000, ['nonTaxableAllowances' => 2000]);   // 25550.0
TaxableIncome::monthly(50000, ['mandatoryDeductions' => 3200]);    // 46800.0
```

---

### `TakeHome::netTakeHome(float $monthlySalary, array $opts = []): array`

Net take-home: gross minus mandatory SSS / PhilHealth / Pag-IBIG employee contributions, and (optionally, v0.2+) BIR withholding tax.

Return shape (always):
```php
[
  'gross' => float,
  'sss' => array,         // full Sss::contribution result
  'philHealth' => array,  // full PhilHealth::contribution result
  'pagIbig' => array,     // full PagIbig::contribution result
  'totalDeductions' => float,
  'net' => float,
  // when ['includeWT' => true]:
  'taxableIncome'  => float,
  'withholdingTax' => float,
  'netAfterTax'    => float,
]
```

`$opts` keys: `year`, `includeWT` (default false — preserves v0.1 shape), `nonTaxableAllowances` (only used with `includeWT`).

```php
use PhDevUtils\Payroll\TakeHome;

// v0.1 shape (default) — pre-tax:
TakeHome::netTakeHome(30000)['net'];     // 27550.0
TakeHome::netTakeHome(8000)['net'];      // 7190.0
TakeHome::netTakeHome(150000)['net'];    // 145550.0

// v0.2+ with WT:
TakeHome::netTakeHome(30000, ['includeWT' => true])['withholdingTax'];   // 1007.55
TakeHome::netTakeHome(30000, ['includeWT' => true])['netAfterTax'];      // 26542.45

// v0.2+ with non-taxable allowances:
TakeHome::netTakeHome(30000, ['includeWT' => true, 'nonTaxableAllowances' => 2000])['withholdingTax'];
// 707.55
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
