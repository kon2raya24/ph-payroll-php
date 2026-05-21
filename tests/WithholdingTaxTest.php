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

    // Semi-monthly (v0.3)
    public function testSemiMonthlyB1Boundary(): void
    {
        $r = WithholdingTax::semiMonthly(10417);
        $this->assertSame(0.0, $r['wt']);
        $this->assertSame(1, $r['bracket']);
        $this->assertSame('semi-monthly', $r['period']);
    }

    public function testSemiMonthlyB2Boundary(): void
    {
        $this->assertSame(937.5, WithholdingTax::semiMonthly(16667)['wt']);
    }

    public function testSemiMonthlyB3Boundary(): void
    {
        $this->assertSame(4270.7, WithholdingTax::semiMonthly(33333)['wt']);
    }

    public function testSemiMonthlyB4Boundary(): void
    {
        $this->assertSame(16770.7, WithholdingTax::semiMonthly(83333)['wt']);
    }

    public function testSemiMonthlyB5Boundary(): void
    {
        $this->assertSame(91770.7, WithholdingTax::semiMonthly(333333)['wt']);
    }

    public function testSemiMonthlyB6(): void
    {
        $r = WithholdingTax::semiMonthly(500000);
        $this->assertSame(150104.15, $r['wt']);
        $this->assertSame(6, $r['bracket']);
    }

    // Weekly (v0.3)
    public function testWeeklyB1Boundary(): void
    {
        $this->assertSame(0.0, WithholdingTax::weekly(4808)['wt']);
    }

    public function testWeeklyB2Boundary(): void
    {
        $this->assertSame(432.6, WithholdingTax::weekly(7692)['wt']);
    }

    public function testWeeklyB3Boundary(): void
    {
        $this->assertSame(1971.2, WithholdingTax::weekly(15385)['wt']);
    }

    public function testWeeklyB4Boundary(): void
    {
        $this->assertSame(7740.45, WithholdingTax::weekly(38462)['wt']);
    }

    public function testWeeklyB5Boundary(): void
    {
        $this->assertSame(42355.65, WithholdingTax::weekly(153846)['wt']);
    }

    public function testWeeklyB6(): void
    {
        $r = WithholdingTax::weekly(200000);
        $this->assertSame(58509.55, $r['wt']);
        $this->assertSame(6, $r['bracket']);
    }

    // Daily (v0.3)
    public function testDailyB1Boundary(): void
    {
        $this->assertSame(0.0, WithholdingTax::daily(685)['wt']);
    }

    public function testDailyB2Boundary(): void
    {
        $this->assertSame(61.65, WithholdingTax::daily(1096)['wt']);
    }

    public function testDailyB3Boundary(): void
    {
        $this->assertSame(280.85, WithholdingTax::daily(2192)['wt']);
    }

    public function testDailyB4Boundary(): void
    {
        $this->assertSame(1102.6, WithholdingTax::daily(5479)['wt']);
    }

    public function testDailyB5Boundary(): void
    {
        $this->assertSame(6034.3, WithholdingTax::daily(21918)['wt']);
    }

    public function testDailyB6(): void
    {
        $r = WithholdingTax::daily(30000);
        $this->assertSame(8863.0, $r['wt']);
        $this->assertSame(6, $r['bracket']);
        $this->assertSame('daily', $r['period']);
    }

    public function testSemiMonthlyRejectsUnsupportedYear(): void
    {
        $this->expectException(\OutOfRangeException::class);
        WithholdingTax::semiMonthly(15000, ['year' => 2020]);
    }
}
