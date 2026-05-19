<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll\Tests;

use PhDevUtils\Payroll\PagIbig;
use PHPUnit\Framework\TestCase;

final class PagIbigTest extends TestCase
{
    public function testLowBracket(): void
    {
        $this->assertSame(
            ['mfs' => 1000.0, 'employee' => 10.0, 'employer' => 20.0, 'total' => 30.0],
            PagIbig::contribution(1000)
        );
        $this->assertSame(
            ['mfs' => 1500.0, 'employee' => 15.0, 'employer' => 30.0, 'total' => 45.0],
            PagIbig::contribution(1500)
        );
    }

    public function testHighBracket(): void
    {
        $this->assertSame(
            ['mfs' => 2000.0, 'employee' => 40.0, 'employer' => 40.0, 'total' => 80.0],
            PagIbig::contribution(2000)
        );
        $this->assertSame(
            ['mfs' => 5000.0, 'employee' => 100.0, 'employer' => 100.0, 'total' => 200.0],
            PagIbig::contribution(5000)
        );
    }

    public function testCappedAtMfsCeiling(): void
    {
        $this->assertSame(
            ['mfs' => 10000.0, 'employee' => 200.0, 'employer' => 200.0, 'total' => 400.0],
            PagIbig::contribution(10000)
        );
        $this->assertSame(
            ['mfs' => 10000.0, 'employee' => 200.0, 'employer' => 200.0, 'total' => 400.0],
            PagIbig::contribution(500000)
        );
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PagIbig::contribution(-1);
    }
}
