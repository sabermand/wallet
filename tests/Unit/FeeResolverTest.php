<?php

namespace Tests\Unit;

use App\Services\Fees\FeeResolver;
use PHPUnit\Framework\TestCase;

class FeeResolverTest extends TestCase
{
    public function test_fixed_fee_for_1000_or_less(): void
    {
        $r = new FeeResolver();
        $fee = $r->resolve(1000)->calculate(1000);

        $this->assertSame(2.0, $fee);
    }

    public function test_percentage_fee_for_1001_to_10000(): void
    {
        $r = new FeeResolver();
        $fee = $r->resolve(2000)->calculate(2000);

        // 0.5% of 2000 = 10
        $this->assertSame(10.0, $fee);
    }

    public function test_tiered_fee_for_above_10000(): void
    {
        $r = new FeeResolver();
        $fee = $r->resolve(11000)->calculate(11000);

        $this->assertGreaterThan(2.0, $fee);
    }
}
