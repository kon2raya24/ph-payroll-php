<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\WithholdingTax;
use PhDevUtils\Payroll\TaxableIncome;
use PhDevUtils\Payroll\TakeHome;
use PHPUnit\Framework\TestCase;

final class WithholdingTaxTest extends TestCase
{
    // Bracket 1: TI ≤ 20,833 → 0
    public function testBracket1Zero(): void
    {
        $r = WithholdingTax::monthly(0);
        $this->assertSame(0.0, $r['wt']);
        $this->assertSame(1, $r['bracket']);
        $this->assertSame(0.0, $r['marginalRate']);
    }

    public function testBracket1Ceiling(): void
    {
        $r = WithholdingTax::monthly(20833);
        $this->assertSame(0.0, $r['wt']);
        $this->assertSame(1, $r['bracket']);
    }

    // Bracket 2: 20,833 < TI ≤ 33,333 → 15% over 20,833
    public function testBracket2(): void
    {
        $r = WithholdingTax::monthly(25000);
        $this->assertSame(625.05, $r['wt']);
        $this->assertSame(2, $r['bracket']);
        $this->assertSame(0.15, $r['marginalRate']);
    }

    public function testBracket2Ceiling(): void
    {
        $r = WithholdingTax::monthly(33333);
        $this->assertSame(1875.0, $r['wt']);
        $this->assertSame(2, $r['bracket']);
    }

    // Bracket 3: 33,333 < TI ≤ 66,667 → 1,875 + 20% over 33,333
    public function testBracket3(): void
    {
        $r = WithholdingTax::monthly(50000);
        $this->assertSame(5208.4, $r['wt']);
        $this->assertSame(3, $r['bracket']);
        $this->assertSame(0.2, $r['marginalRate']);
    }

    public function testBracket3Ceiling(): void
    {
        $r = WithholdingTax::monthly(66667);
        $this->assertSame(8541.8, $r['wt']);
        $this->assertSame(3, $r['bracket']);
    }

    // Bracket 4
    public function testBracket4(): void
    {
        $r = WithholdingTax::monthly(100000);
        $this->assertSame(16875.05, $r['wt']);
        $this->assertSame(4, $r['bracket']);
        $this->assertSame(0.25, $r['marginalRate']);
    }

    public function testBracket4Ceiling(): void
    {
        $r = WithholdingTax::monthly(166667);
        $this->assertSame(33541.8, $r['wt']);
        $this->assertSame(4, $r['bracket']);
    }

    // Bracket 5
    public function testBracket5(): void
    {
        $r = WithholdingTax::monthly(200000);
        $this->assertSame(43541.7, $r['wt']);
        $this->assertSame(5, $r['bracket']);
        $this->assertSame(0.3, $r['marginalRate']);
    }

    public function testBracket5Ceiling(): void
    {
        $r = WithholdingTax::monthly(666667);
        $this->assertSame(183541.8, $r['wt']);
        $this->assertSame(5, $r['bracket']);
    }

    // Bracket 6
    public function testBracket6(): void
    {
        $r = WithholdingTax::monthly(1000000);
        $this->assertSame(300208.35, $r['wt']);
        $this->assertSame(6, $r['bracket']);
        $this->assertSame(0.35, $r['marginalRate']);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        WithholdingTax::monthly(-1);
    }

    public function testRejectsUnsupportedYear(): void
    {
        $this->expectException(\OutOfRangeException::class);
        WithholdingTax::monthly(50000, ['year' => 2020]);
    }

    // TaxableIncome::monthly
    public function testTaxableIncomeAutoMandatories(): void
    {
        $this->assertSame(27550.0, TaxableIncome::monthly(30000));
    }

    public function testTaxableIncomeExplicitMandatory(): void
    {
        $this->assertSame(28000.0, TaxableIncome::monthly(30000, ['mandatoryDeductions' => 2000]));
    }

    public function testTaxableIncomeAllowances(): void
    {
        $this->assertSame(25550.0, TaxableIncome::monthly(30000, ['nonTaxableAllowances' => 2000]));
    }

    public function testTaxableIncomeClampsZero(): void
    {
        $this->assertSame(0.0, TaxableIncome::monthly(5000, ['mandatoryDeductions' => 0, 'nonTaxableAllowances' => 10000]));
    }

    // TakeHome integration with includeWT
    public function testTakeHomeWithWT30k(): void
    {
        $r = TakeHome::netTakeHome(30000, ['includeWT' => true]);
        $this->assertSame(2450.0, $r['totalDeductions']);
        $this->assertSame(27550.0, $r['net']);
        $this->assertSame(27550.0, $r['taxableIncome']);
        $this->assertSame(1007.55, $r['withholdingTax']);
        $this->assertSame(26542.45, $r['netAfterTax']);
    }

    public function testTakeHomeLowEarnerNoWT(): void
    {
        $r = TakeHome::netTakeHome(15000, ['includeWT' => true]);
        $this->assertSame(1325.0, $r['totalDeductions']);
        $this->assertSame(13675.0, $r['net']);
        $this->assertSame(13675.0, $r['taxableIncome']);
        $this->assertSame(0.0, $r['withholdingTax']);
        $this->assertSame(13675.0, $r['netAfterTax']);
    }

    public function testTakeHomeWithAllowances(): void
    {
        $r = TakeHome::netTakeHome(30000, ['includeWT' => true, 'nonTaxableAllowances' => 2000]);
        $this->assertSame(25550.0, $r['taxableIncome']);
        $this->assertSame(707.55, $r['withholdingTax']);
    }

    public function testTakeHomeDefaultPreservesV01Shape(): void
    {
        $r = TakeHome::netTakeHome(30000);
        $this->assertArrayNotHasKey('taxableIncome', $r);
        $this->assertArrayNotHasKey('withholdingTax', $r);
        $this->assertArrayNotHasKey('netAfterTax', $r);
    }
}
