<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorReliability;
use App\Models\GeoStrip;
use App\Models\VideoSlot;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SlotPublisherService
{
    public function __construct(
        protected IncentiveService $incentives
    ) {}

    /**
     * Publish tonight's night-band slots for all active strips: 19:00-06:59 IST.
     */
    public function publishNightSlots(Carbon $targetDateIST): void
    {
        $hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
        $strips = GeoStrip::active()->get();

        DB::transaction(function () use ($hoursIST, $strips, $targetDateIST) {
            foreach ($strips as $strip) {
                foreach ($hoursIST as $hIst) {
                    // Convert IST date+hour to UTC date+hour
                    $ist = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $targetDateIST->format('Y-m-d') . ' ' . str_pad((string)$hIst, 2, '0', STR_PAD_LEFT) . ':00:00', 'Asia/Kolkata');
                    if (in_array($hIst, [0,1,2,3,4,5,6], true)) {
                        // For 00-06 IST, advance date to next day to represent the "tonight" continuation
                        $ist = $ist->addDay();
                    }
                    $utc = $ist->setTimezone('UTC');
                    $slotDate = $utc->toDateString();
                    $hourUTC = (int) $utc->format('G');

                    // Compute payout using incentives; reliability as average 0.8 by default
                    $payout = $this->incentives->payoutFor($hIst, $strip, 0.8, 1.0);

                    foreach (['primary', 'bench'] as $role) {
                        VideoSlot::query()->firstOrCreate(
                            [
                                'strip_id' => $strip->id,
                                'slot_date' => $slotDate,
                                'hour_24' => $hourUTC,
                                'role' => $role,
                            ],
                            [
                                'status' => 'open',
                                'payout_offer' => $payout,
                                'demand_score' => 1.00,
                            ]
                        );
                    }
                }
            }
        });
    }

    /**
     * Ensure >=1 primary per strip-hour for tonight (IST) exists as OPEN or COMMITTED.
     */
    public function backfillUncoveredGapsForTonight(): void
    {
        $todayIST = Carbon::now('Asia/Kolkata')->startOfDay();
        $this->publishNightSlots($todayIST);
    }
}

