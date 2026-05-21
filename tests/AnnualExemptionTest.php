<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\AnnualExemption;
use PHPUnit\Framework\TestCase;

final class AnnualExemptionTest extends TestCase
{
    public function testWithinCap(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 30000, 'otherBonuses' => 50000]);
        $this->assertSame(80000.0, $r['totalReceived']);
        $this->assertSame(80000.0, $r['exempt']);
        $this->assertSame(0.0, $r['taxableExcess']);
        $this->assertSame(90000.0, $r['cap']);
    }

    public function testOverCap(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 30000, 'otherBonuses' => 100000]);
        $this->assertSame(130000.0, $r['totalReceived']);
        $this->assertSame(90000.0, $r['exempt']);
        $this->assertSame(40000.0, $r['taxableExcess']);
    }

    public function testExactlyAtCap(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 90000]);
        $this->assertSame(90000.0, $r['exempt']);
        $this->assertSame(0.0, $r['taxableExcess']);
    }

    public function testThirteenthMonthOnly(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 25000]);
        $this->assertSame(25000.0, $r['totalReceived']);
        $this->assertSame(25000.0, $r['exempt']);
    }

    public function testHighEarner(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 200000, 'otherBonuses' => 500000]);
        $this->assertSame(700000.0, $r['totalReceived']);
        $this->assertSame(90000.0, $r['exempt']);
        $this->assertSame(610000.0, $r['taxableExcess']);
    }

    public function testRejectsNegative13th(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AnnualExemption::ninetyK(['thirteenthMonthPay' => -1]);
    }

    public function testRejectsNegativeOtherBonuses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AnnualExemption::ninetyK(['thirteenthMonthPay' => 0, 'otherBonuses' => -1]);
    }

    public function testIncludesAuthorityMetadata(): void
    {
        $r = AnnualExemption::ninetyK(['thirteenthMonthPay' => 0]);
        $this->assertStringContainsString('RA 10963', $r['_source']['authority']);
    }
}
