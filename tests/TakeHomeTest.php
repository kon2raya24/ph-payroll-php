<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\TakeHome;
use PHPUnit\Framework\TestCase;

final class TakeHomeTest extends TestCase
{
    public function testWorkedExample30k(): void
    {
        $r = TakeHome::netTakeHome(30000);
        $this->assertSame(30000.0, $r['gross']);
        $this->assertSame(1500.0, $r['sss']['employeeShare']);
        $this->assertSame(750.0, $r['philHealth']['employee']);
        $this->assertSame(200.0, $r['pagIbig']['employee']);
        $this->assertSame(2450.0, $r['totalDeductions']);
        $this->assertSame(27550.0, $r['net']);
    }

    public function testLowEarner8k(): void
    {
        $r = TakeHome::netTakeHome(8000);
        $this->assertSame(400.0, $r['sss']['employeeShare']);
        $this->assertSame(250.0, $r['philHealth']['employee']);
        $this->assertSame(160.0, $r['pagIbig']['employee']);
        $this->assertSame(810.0, $r['totalDeductions']);
        $this->assertSame(7190.0, $r['net']);
    }

    public function testHighEarner150k(): void
    {
        $r = TakeHome::netTakeHome(150000);
        $this->assertSame(1750.0, $r['sss']['employeeShare']);
        $this->assertSame(2500.0, $r['philHealth']['employee']);
        $this->assertSame(200.0, $r['pagIbig']['employee']);
        $this->assertSame(4450.0, $r['totalDeductions']);
        $this->assertSame(145550.0, $r['net']);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TakeHome::netTakeHome(-100);
    }
}
