<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\PhilHealth;
use PHPUnit\Framework\TestCase;

final class PhilHealthTest extends TestCase
{
    public function testTwentyFiveK(): void
    {
        $this->assertSame(
            ['total' => 1250.0, 'employee' => 625.0, 'employer' => 625.0],
            PhilHealth::contribution(25000)
        );
    }

    public function testFloor(): void
    {
        $this->assertSame(
            ['total' => 500.0, 'employee' => 250.0, 'employer' => 250.0],
            PhilHealth::contribution(5000)
        );
        $this->assertSame(
            ['total' => 500.0, 'employee' => 250.0, 'employer' => 250.0],
            PhilHealth::contribution(10000)
        );
    }

    public function testCeiling(): void
    {
        $this->assertSame(
            ['total' => 5000.0, 'employee' => 2500.0, 'employer' => 2500.0],
            PhilHealth::contribution(100000)
        );
        $this->assertSame(
            ['total' => 5000.0, 'employee' => 2500.0, 'employer' => 2500.0],
            PhilHealth::contribution(200000)
        );
    }

    public function testCentavoRoundingPolicy(): void
    {
        $r = PhilHealth::contribution(10001);
        $this->assertSame(250.03, $r['employee']);
        $this->assertSame(250.03, $r['employer']);
        $this->assertSame(500.06, $r['total']);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PhilHealth::contribution(-1);
    }
}
