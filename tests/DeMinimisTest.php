<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\DeMinimis;
use PHPUnit\Framework\TestCase;

final class DeMinimisTest extends TestCase
{
    public function testMonthlyAllWithinCaps(): void
    {
        $r = DeMinimis::monthly(['riceSubsidy' => 2500, 'laundryAllowance' => 400, 'medicalCashDependents' => 300]);
        $this->assertSame(3200.0, $r['nonTaxable']);
        $this->assertSame(0.0, $r['taxableExcess']);
        $this->assertCount(3, $r['breakdown']);
    }

    public function testMonthlyRiceOverCap(): void
    {
        $r = DeMinimis::monthly(['riceSubsidy' => 3000, 'laundryAllowance' => 400]);
        $this->assertSame(2900.0, $r['nonTaxable']);
        $this->assertSame(500.0, $r['taxableExcess']);
    }

    public function testMonthlyPerCategoryIsolation(): void
    {
        $r = DeMinimis::monthly(['riceSubsidy' => 2800, 'laundryAllowance' => 0]);
        $this->assertSame(2500.0, $r['nonTaxable']);
        $this->assertSame(300.0, $r['taxableExcess']);
    }

    public function testMonthlyOmittedCategoriesSkipped(): void
    {
        $r = DeMinimis::monthly(['riceSubsidy' => 1000]);
        $this->assertCount(1, $r['breakdown']);
        $this->assertSame(1000.0, $r['nonTaxable']);
    }

    public function testMonthlyRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DeMinimis::monthly(['riceSubsidy' => -100]);
    }

    public function testMonthlyIncludesSourceMetadata(): void
    {
        $r = DeMinimis::monthly(['riceSubsidy' => 1000]);
        $this->assertSame('2025-12-22', $r['_source']['effective_from']);
        $this->assertStringContainsString('29-2025', $r['_source']['source']);
    }

    public function testAnnualAllWithinCaps(): void
    {
        $r = DeMinimis::annual([
            'uniformClothing' => 8000,
            'medicalAssistance' => 12000,
            'employeeAchievementAwards' => 12000,
            'giftsChristmasAnniversary' => 6000,
            'cbaProductivityIncentives' => 12000,
        ]);
        $this->assertSame(50000.0, $r['nonTaxable']);
        $this->assertSame(0.0, $r['taxableExcess']);
    }

    public function testAnnualMedicalOverCap(): void
    {
        $r = DeMinimis::annual(['medicalAssistance' => 15000]);
        $this->assertSame(12000.0, $r['nonTaxable']);
        $this->assertSame(3000.0, $r['taxableExcess']);
    }

    public function testAnnualMonetizedLeaveWithinCap(): void
    {
        $r = DeMinimis::annual(['monetizedUnusedLeavePrivate' => ['days' => 10, 'dailyRate' => 1000]]);
        $this->assertSame(10000.0, $r['nonTaxable']);
        $this->assertSame(0.0, $r['taxableExcess']);
    }

    public function testAnnualMonetizedLeaveOverCap(): void
    {
        $r = DeMinimis::annual(['monetizedUnusedLeavePrivate' => ['days' => 15, 'dailyRate' => 1000]]);
        $this->assertSame(12000.0, $r['nonTaxable']);
        $this->assertSame(3000.0, $r['taxableExcess']);
    }

    public function testAnnualRejectsNegativeDays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DeMinimis::annual(['monetizedUnusedLeavePrivate' => ['days' => -1, 'dailyRate' => 1000]]);
    }
}
