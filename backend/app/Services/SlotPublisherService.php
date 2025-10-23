<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeoStrip;
use App\Models\VideoSlot;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
            $nightDayOfWeek = strtolower($targetDateIST->format('l'));
            foreach ($strips as $strip) {
                foreach ($hoursIST as $hIst) {
                    // Convert IST date+hour to UTC date+hour
                    $ist = CarbonImmutable::createFromFormat(
                        'Y-m-d H:i:s',
                        $targetDateIST->format('Y-m-d') . ' ' . str_pad((string) $hIst, 2, '0', STR_PAD_LEFT) . ':00:00',
                        'Asia/Kolkata'
                    );
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
                                'slot_day_of_week' => $nightDayOfWeek,
                            ]
                        );
                    }
                }
            }
        });
    }

    /**
     * Ensure the entire night window containing the given UTC date/hour exists.
     */
    public function ensureNightSlotsForUtcWindow(string $utcDate, int $utcHour): void
    {
        try {
            $utc = CarbonImmutable::createFromFormat('Y-m-d H', sprintf('%s %02d', $utcDate, $utcHour), 'UTC');
        } catch (\Throwable $e) {
            return;
        }

        $anchorIst = $this->nightAnchorIstDateFromUtc($utc);
        $this->publishNightSlots($this->toMutableIstDate($anchorIst));
    }

    /**
     * Ensure nightly slots exist for a given IST calendar date (YYYY-MM-DD).
     */
    public function ensureNightSlotsForIstDate(string $dateIst): void
    {
        try {
            $target = Carbon::createFromFormat('Y-m-d', $dateIst, 'Asia/Kolkata')->startOfDay();
        } catch (\Throwable $e) {
            return;
        }
        $this->publishNightSlots($target);
    }

    /**
     * Ensure nightly slots exist for the recurring window starting today.
     */
    public function ensureUpcomingNightWindow(?int $days = null): void
    {
        $span = $days ?? (int) config('video.night.recurring_commit_days', 60);
        $span = max(1, $span);

        $start = Carbon::now('Asia/Kolkata')->startOfDay();
        $this->publishNightSlotsForRange($start, $span);
    }

    /**
     * Ensure the configured recurring window exists for the slot being committed/released.
     */
    public function ensureRecurringWindowForSlot(VideoSlot $slot): void
    {
        $span = (int) config('video.night.recurring_commit_days', 60);
        $span = max(1, $span);

        $utcStart = $this->slotUtcDateTime($slot);
        $anchorIst = $this->nightAnchorIstDateFromUtc($utcStart);

        $this->publishNightSlotsForRange($this->toMutableIstDate($anchorIst), $span);
    }

    /**
     * Publish a continuous range of IST calendar days in one go.
     */
    public function publishNightSlotsForRange(Carbon $startDateIst, int $days): void
    {
        $days = max(1, min($days, (int) config('video.night.publish_span_max_days', 180)));
        $cursor = $startDateIst->copy();

        for ($i = 0; $i < $days; $i++) {
            $this->publishNightSlots($cursor->copy());
            $cursor->addDay();
        }
    }

    /**
     * Ensure >=1 primary per strip-hour for tonight (IST) exists as OPEN or COMMITTED.
     */
    public function backfillUncoveredGapsForTonight(): void
    {
        $todayIST = Carbon::now('Asia/Kolkata')->startOfDay();
        $this->publishNightSlots($todayIST);
    }

    private function slotUtcDateTime(VideoSlot $slot): CarbonImmutable
    {
        $dateAttr = $slot->getAttribute('slot_date');
        if ($dateAttr instanceof CarbonInterface) {
            $date = $dateAttr->format('Y-m-d');
        } else {
            $date = (string) $dateAttr;
        }
        $hour = (int) $slot->getAttribute('hour_24');

        return CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            sprintf('%s %02d:00:00', $date, $hour),
            'UTC'
        );
    }

    private function nightAnchorIstDateFromUtc(CarbonImmutable $utc): CarbonImmutable
    {
        $ist = $utc->setTimezone('Asia/Kolkata');
        $anchor = $ist->startOfDay();
        if ($ist->hour <= 6) {
            $anchor = $anchor->subDay();
        }
        return $anchor->startOfDay();
    }

    private function toMutableIstDate(CarbonImmutable $ist): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $ist->format('Y-m-d'), 'Asia/Kolkata')->startOfDay();
    }
}
