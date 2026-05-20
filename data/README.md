# ph-payroll data

This directory holds the contribution-rate / bracket tables that drive the payroll calculators. Each file is **versioned by effective date** and includes its source citation.

## Files

| File | Authority | Effective from | Applies to |
|---|---|---|---|
| `sss-table-2025.json` | SSS Circular 2024-006 (RA 11199) | 2025-01-01 | 2025, 2026 |
| `philhealth-rate-2026.json` | UHC Law final adjustment (RA 11223) | 2024-01-01 | 2024, 2025, 2026 |
| `pagibig-rate-2024.json` | HDMF Circular 460 (RA 9679) | 2024-02-01 | 2024, 2025, 2026 |

## Why this layout

Rates **change**. The SSS rate moved 13% → 14% → 15% between 2023 and 2025. PhilHealth moved 4% → 4.5% → 5% between 2023 and 2025. Pag-IBIG raised the MFS cap from ₱5,000 to ₱10,000 in February 2024.

This package keeps each version of each table as its own JSON file with explicit `effective_from` / `effective_until` metadata. Calculator functions accept an optional `{ year }` parameter — pass it and the right table loads. Default is the most recent table.

When a new circular issues, the workflow is:
1. Add a new file `sss-table-YYYY.json` (or equivalent).
2. Set the previous table's `effective_until` to the day before the new one.
3. Update the README table above.
4. Bump the package version.

The old file stays in the repo — users who haven't upgraded keep using their pinned version with the older table.

## Accuracy caveat

The official SSS contribution table is published as **images** on sss.gov.ph, not as a structured document. The bracket boundaries in this package are reproduced algorithmically (₱500 MSC increments, rounded to nearest from the compensation figure). If you spot a discrepancy against an official SSS-issued payslip, please file an issue with the example.

PhilHealth and Pag-IBIG rates are simple percentage formulas with floor / ceiling — no bracket table.

## If a rate changed and we missed it

Open an issue with:
1. Link to the official circular (SSS / PhilHealth / Pag-IBIG / BIR)
2. The effective date
3. The specific bracket or rate that changed

We'd rather know fast and ship a patch than have wrong numbers in production payrolls.
