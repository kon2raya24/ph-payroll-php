<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\ThirteenthMonth;
use PHPUnit\Framework\TestCase;

final class ThirteenthMonthTest extends TestCase
{
    public function testFromTotalFullYear(): void
    {
        $this->assertSame(30000.0, ThirteenthMonth::fromTotal(360000));
    }

    public function testFromTotalSixMonths(): void
    {
        $this->assertSame(15000.0, ThirteenthMonth::fromTotal(180000));
    }

    public function testFromTotalZero(): void
    {
        $this->assertSame(0.0, ThirteenthMonth::fromTotal(0));
    }

    public function testFromMonthlyFullYear(): void
    {
        $this->assertSame(30000.0, ThirteenthMonth::fromMonthly(30000));
        $this->assertSame(30000.0, ThirteenthMonth::fromMonthly(30000, 12));
    }

    public function testFromMonthlyPartialYear(): void
    {
        $this->assertSame(15000.0, ThirteenthMonth::fromMonthly(30000, 6));
        $this->assertSame(2500.0, ThirteenthMonth::fromMonthly(30000, 1));
        $this->assertSame(0.0, ThirteenthMonth::fromMonthly(30000, 0));
    }

    public function testFromMonthlyRejectsTooManyMonths(): void
    {
        $this->expectException(\OutOfRangeException::class);
        ThirteenthMonth::fromMonthly(30000, 13);
    }

    public function testFromTotalRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ThirteenthMonth::fromTotal(-1);
    }
}
