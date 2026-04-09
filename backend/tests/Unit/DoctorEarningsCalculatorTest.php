<?php

namespace Tests\Unit;

use App\Support\DoctorEarningsCalculator;
use PHPUnit\Framework\TestCase;

class DoctorEarningsCalculatorTest extends TestCase
{
    public function test_it_calculates_actual_earnings_after_gst_and_flat_deduction(): void
    {
        $result = DoctorEarningsCalculator::fromCurrentPaymentPaise(100000);

        $this->assertSame(100000, $result['current_payment_paise']);
        $this->assertSame(18000, $result['gst_deduction_paise']);
        $this->assertSame(15000, $result['flat_deduction_paise']);
        $this->assertSame(67000, $result['actual_earnings_paise']);
        $this->assertSame(670.0, $result['actual_earnings_inr']);
    }

    public function test_it_caps_the_flat_deduction_when_gst_leaves_less_than_rs_150(): void
    {
        $result = DoctorEarningsCalculator::fromCurrentPaymentPaise(17000);

        $this->assertSame(17000, $result['current_payment_paise']);
        $this->assertSame(3060, $result['gst_deduction_paise']);
        $this->assertSame(13940, $result['flat_deduction_paise']);
        $this->assertSame(0, $result['actual_earnings_paise']);
        $this->assertSame(0.0, $result['actual_earnings_inr']);
    }
}
