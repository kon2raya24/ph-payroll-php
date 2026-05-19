<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\Sss;
use PHPUnit\Framework\TestCase;

final class SssTest extends TestCase
{
    public function testTwentyKAtRegularCap(): void
    {
        $r = Sss::contribution(20000);
        $this->assertSame(20000, $r['msc']);
        $this->assertSame(['employee' => 1000.0, 'employer' => 2000.0, 'total' => 3000.0], $r['regular']);
        $this->assertSame(['employee' => 0.0, 'employer' => 0.0, 'total' => 0.0], $r['mpf']);
        $this->assertSame(30.0, $r['ec']);
        $this->assertSame(1000.0, $r['employeeShare']);
        $this->assertSame(2030.0, $r['employerShare']);
        $this->assertSame(3030.0, $r['total']);
    }

    public function testThirtyKSplitsBetweenRegularAndMpf(): void
    {
        $r = Sss::contribution(30000);
        $this->assertSame(30000, $r['msc']);
        $this->assertSame(['employee' => 1000.0, 'employer' => 2000.0, 'total' => 3000.0], $r['regular']);
        $this->assertSame(['employee' => 500.0, 'employer' => 1000.0, 'total' => 1500.0], $r['mpf']);
        $this->assertSame(1500.0, $r['employeeShare']);
        $this->assertSame(3030.0, $r['employerShare']);
    }

    public function testFiftyKCapsAtMaxMsc(): void
    {
        $r = Sss::contribution(50000);
        $this->assertSame(35000, $r['msc']);
        $this->assertSame(3000.0, $r['regular']['total']);
        $this->assertSame(2250.0, $r['mpf']['total']);
    }

    public function testLowSalaryFloorsToMinMsc(): void
    {
        $r = Sss::contribution(3000);
        $this->assertSame(5000, $r['msc']);
        $this->assertSame(['employee' => 250.0, 'employer' => 500.0, 'total' => 750.0], $r['regular']);
        $this->assertSame(10.0, $r['ec']);
    }

    public function testEcThreshold(): void
    {
        $this->assertSame(10.0, Sss::contribution(14500)['ec']);
        $this->assertSame(30.0, Sss::contribution(15000)['ec']);
    }

    public function testMscBracketRounding(): void
    {
        $this->assertSame(5500, Sss::contribution(5250)['msc']);
        $this->assertSame(5000, Sss::contribution(5249.99)['msc']);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Sss::contribution(-1);
    }

    public function testRejectsUnsupportedYear(): void
    {
        $this->expectException(\OutOfRangeException::class);
        Sss::contribution(20000, ['year' => 2020]);
    }
}
