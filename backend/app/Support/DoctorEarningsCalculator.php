<?php

namespace App\Support;

class DoctorEarningsCalculator
{
    public const GST_RATE = 0.18;
    public const FLAT_DEDUCTION_PAISE = 15_000;

    public static function fromCurrentPaymentPaise(?int $currentPaymentPaise): array
    {
        $currentPaymentPaise = max((int) ($currentPaymentPaise ?? 0), 0);
        $gstDeductionPaise = (int) max(round($currentPaymentPaise * self::GST_RATE), 0);
        $remainingAfterGstPaise = max($currentPaymentPaise - $gstDeductionPaise, 0);
        $flatDeductionPaise = min(self::FLAT_DEDUCTION_PAISE, $remainingAfterGstPaise);
        $actualEarningsPaise = max($remainingAfterGstPaise - $flatDeductionPaise, 0);

        return [
            'gst_rate' => self::GST_RATE,
            'current_payment_paise' => $currentPaymentPaise,
            'current_payment_inr' => round($currentPaymentPaise / 100, 2),
            'gst_deduction_paise' => $gstDeductionPaise,
            'gst_deduction_inr' => round($gstDeductionPaise / 100, 2),
            'flat_deduction_paise' => $flatDeductionPaise,
            'flat_deduction_inr' => round($flatDeductionPaise / 100, 2),
            'actual_earnings_paise' => $actualEarningsPaise,
            'actual_earnings_inr' => round($actualEarningsPaise / 100, 2),
        ];
    }
}
