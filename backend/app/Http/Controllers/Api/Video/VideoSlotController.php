<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Models\VideoSlot;
use App\Services\CommitmentService;
use App\Services\SlotPublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class VideoSlotController extends Controller
{
    public function __construct(
        private readonly CommitmentService $commitments,
        private readonly SlotPublisherService $publisher
    )
    {
    }

    // GET /api/video/slots/open?date=YYYY-MM-DD&strip_id=
    public function openSlots(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $dayInput = $request->query('day');
        $stripId = $request->query('strip_id');

        $normalizedDay = null;
        if ($dayInput !== null && $dayInput !== '') {
            $normalizedDay = $this->normalizeDayOfWeek((string) $dayInput);
            if ($normalizedDay === null) {
                return response()->json(['error' => 'day must be a valid weekday name'], 422);
            }
        }

        if (($date === null || $date === '') && $normalizedDay === null) {
            return response()->json(['error' => 'either date (YYYY-MM-DD) or day is required'], 422);
        }

        if ($date !== null && $date !== '') {
            $this->publisher->ensureNightSlotsForIstDate((string) $date);
        } elseif ($normalizedDay !== null) {
            $this->publisher->ensureUpcomingNightWindow();
        }

        $q = VideoSlot::query()
            ->openForMarketplace(
                $date !== null && $date !== '' ? (string) $date : null,
                $stripId ? (int) $stripId : null,
                $normalizedDay
            )
            ->orderBy('slot_date')
            ->orderBy('hour_24')
            ->orderBy('strip_id');

        return response()->json([
            'date'     => $date !== null && $date !== '' ? (string) $date : null,
            'day'      => $normalizedDay,
            'strip_id' => $stripId ? (int) $stripId : null,
            'slots'    => $q->get(),
        ]);
    }

    // POST /api/video/slots/{slot}/commit
    public function commit(Request $request, int $slot): JsonResponse
    {
        $slotModel = VideoSlot::query()->find($slot);
        if (!$slotModel) {
            return response()->json(['error' => 'Slot not found'], 404);
        }

        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);

        try {
            $this->publisher->ensureRecurringWindowForSlot($slotModel);

            $updated = DB::transaction(function () use ($slotModel, $data) {
                // lock the row to avoid double-commit
                /** @var VideoSlot $row */
                $row = VideoSlot::query()->whereKey($slotModel->id)->lockForUpdate()->firstOrFail();

                if (!in_array($row->status, ['open', 'held'], true)) {
                    throw new \RuntimeException('Slot is not open for commit');
                }

                $row->status = 'committed';
                $row->committed_doctor_id = (int) $data['doctor_id'];
                // (optional) set due/check-in times here if you want
                $row->save();

                $this->commitFutureSlots($row, (int) $data['doctor_id']);

                return $row->fresh();
            });

            return response()->json(['slot' => $updated], 200);
        } catch (\Throwable $e) {
            // conflict or validation-like business error
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    // DELETE /api/video/slots/{slot}/release
    public function release(Request $request, int $slot): JsonResponse
    {
        $slotModel = VideoSlot::query()->find($slot);
        if (!$slotModel) {
            return response()->json(['error' => 'Slot not found'], 404);
        }

        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $released = $this->commitments->releaseSlot($slotModel, (int) $data['doctor_id'], $data['reason'] ?? null);

            $this->publisher->ensureRecurringWindowForSlot($slotModel);

            DB::transaction(function () use ($released, $data) {
                $this->releaseFutureSlots($released, (int) $data['doctor_id'], $data['reason'] ?? null);
            });

            return response()->json(['slot' => $released], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    // POST /api/video/slots/{slot}/checkin
    public function checkin(Request $request, int $slot): JsonResponse
    {
        $slotModel = VideoSlot::query()->find($slot);
        if (!$slotModel) {
            return response()->json(['error' => 'Slot not found'], 404);
        }

        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);

        try {
            $updated = DB::transaction(function () use ($slotModel, $data) {
                /** @var VideoSlot $row */
                $row = VideoSlot::query()->whereKey($slotModel->id)->lockForUpdate()->firstOrFail();

                // Dev-safety: ensure same doctor is checking in
                if ((int) $row->committed_doctor_id !== (int) $data['doctor_id']) {
                    throw new \RuntimeException('Not your committed slot');
                }

                // mark checked-in and move to in_progress (simple dev logic)
                $row->checked_in_at = now()->toImmutable(); // UTC timestamps
                if (in_array($row->status, ['committed', 'held'], true)) {
                    $row->status = 'in_progress';
                }
                $row->save();

                return $row->fresh();
            });

            return response()->json(['status' => 'ok', 'slot' => $updated], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    private function commitFutureSlots(VideoSlot $slot, int $doctorId): void
    {
        $baseDate = $this->slotDateString($slot);
        $endDate = $this->recurringWindowEnd($slot)->toDateString();

        $futureSlots = VideoSlot::query()
            ->where('id', '!=', $slot->id)
            ->where('strip_id', $slot->strip_id)
            ->where('hour_24', $slot->hour_24)
            ->where('role', $slot->role)
            ->whereBetween('slot_date', [$baseDate, $endDate])
            ->whereIn('status', ['open', 'held'])
            ->lockForUpdate()
            ->get();

        foreach ($futureSlots as $future) {
            $future->status = 'committed';
            $future->committed_doctor_id = $doctorId;
            $future->save();
        }
    }

    private function releaseFutureSlots(VideoSlot $slot, int $doctorId, ?string $reason = null): void
    {
        $baseDate = $this->slotDateString($slot);
        $endDate = $this->recurringWindowEnd($slot)->toDateString();

        $futureSlots = VideoSlot::query()
            ->where('id', '!=', $slot->id)
            ->where('strip_id', $slot->strip_id)
            ->where('hour_24', $slot->hour_24)
            ->where('role', $slot->role)
            ->whereBetween('slot_date', [$baseDate, $endDate])
            ->where('committed_doctor_id', $doctorId)
            ->whereIn('status', ['committed', 'held'])
            ->lockForUpdate()
            ->get();

        $releasedAt = now('UTC')->toIso8601String();

        foreach ($futureSlots as $future) {
            $future->status = 'open';
            $future->committed_doctor_id = null;
            $future->checkin_due_at = null;
            $future->checked_in_at = null;
            $future->in_progress_at = null;
            $future->finished_at = null;

            $meta = $future->meta ?? [];
            $meta['released_at'] = $releasedAt;
            $meta['released_by_doctor'] = $doctorId;
            if ($reason !== null && $reason !== '') {
                $meta['release_reason'] = $reason;
            }
            $future->meta = $meta;

            $future->save();
        }
    }

    private function recurringWindowEnd(VideoSlot $slot): CarbonImmutable
    {
        $days = (int) config('video.night.recurring_commit_days', 60);
        $days = max(1, $days);

        return $this->slotDateImmutable($slot)->addDays($days - 1);
    }

    private function slotDateString(VideoSlot $slot): string
    {
        $value = $slot->getAttribute('slot_date');
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    private function slotDateImmutable(VideoSlot $slot): CarbonImmutable
    {
        $value = $slot->getAttribute('slot_date');
        if ($value instanceof CarbonImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        return CarbonImmutable::createFromFormat('Y-m-d', (string) $value, 'UTC');
    }

    // GET /api/video/slots/doctor?doctor_id=ID&date=YYYY-MM-DD&tz=IST
    // public function doctorSlots(Request $request): JsonResponse
    // {
    //     $doctorId = (int) $request->query('doctor_id');
    //     $date     = (string) $request->query('date');
    //     $tz       = strtoupper((string) $request->query('tz', 'IST'));

    //     if (!$doctorId || !$date) {
    //         return response()->json(['error' => 'doctor_id and date are required'], 422);
    //     }

    //     // Allowed IST night hours 19..06
    //     $hoursIst = [19,20,21,22,23,0,1,2,3,4,5,6];

    //     // Build (slot_date UTC, hour_24 UTC) pairs for the requested IST date
    //     $pairs = [];
    //     if ($tz === 'IST') {
    //         foreach ($hoursIst as $hIst) {
    //             $hm  = str_pad((string)$hIst, 2, '0', STR_PAD_LEFT) . ':00:00';
    //             $ist = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $hm, 'Asia/Kolkata');
    //             $utc = $ist->setTimezone('UTC');
    //             $pairs[] = [$utc->toDateString(), (int) $utc->format('G')];
    //         }
    //     } else {
    //         // Treat given date as UTC calendar day; include hours 19..06 as provided
    //         foreach ($hoursIst as $h) {
    //             $pairs[] = [$date, (int) $h];
    //         }
    //     }

    //     $query = VideoSlot::query()
    //         ->where('committed_doctor_id', $doctorId)
    //         ->whereIn('status', ['committed','in_progress','done'])
    //         ->where(function ($q) use ($pairs) {
    //             foreach ($pairs as [$d, $h]) {
    //                 $q->orWhere(function ($qq) use ($d, $h) {
    //                     $qq->where('slot_date', $d)->where('hour_24', $h);
    //                 });
    //             }
    //         })
    //         ->orderBy('slot_date')
    //         ->orderBy('hour_24');

    //     return response()->json([
    //         'doctor_id' => $doctorId,
    //         'date'      => $date,
    //         'slots'     => $query->get(),
    //     ]);
    // }

    public function doctorSlots(Request $request): JsonResponse
    {
        $doctorId = (int) $request->query('doctor_id');
        $tz       = strtoupper((string) $request->query('tz', 'IST'));
        $dayInput = $request->query('day');

        $dateParam = $request->query('date');
        $date = $dateParam !== null && $dateParam !== ''
            ? (string) $dateParam
            : now('Asia/Kolkata')->toDateString();

        if (!$doctorId) {
            return response()->json(['error' => 'doctor_id is required'], 422);
        }

        $normalizedDay = null;
        if ($dayInput !== null && $dayInput !== '') {
            $normalizedDay = $this->normalizeDayOfWeek((string) $dayInput);
            if ($normalizedDay === null) {
                return response()->json(['error' => 'day must be a valid weekday name'], 422);
            }
        }

        if ($date === '' && $normalizedDay === null) {
            return response()->json(['error' => 'either date (YYYY-MM-DD) or day is required'], 422);
        }

        // IST night hours → UTC 13..23 + 0..6
        $utcNightHours = array_merge(range(13, 23), range(0, 6));

        $rows = VideoSlot::query()
            ->where('committed_doctor_id', $doctorId)
            ->whereIn('hour_24', $utcNightHours)
            ->whereIn('status', ['committed','in_progress','done']);

        if ($normalizedDay !== null) {
            $rows->where('slot_day_of_week', $normalizedDay);
        } else {
            $rows->where('slot_date', $date);
        }

        $rows = $rows
            ->orderBy('hour_24')
            ->orderBy('strip_id')
            ->orderByRaw("FIELD(role,'primary','bench')")
            ->get();

        $mapped = $rows->map(function ($r) {
            $istHour = ($r->hour_24 + 6) % 24;
            // ✅ safe parse (handles 'YYYY-MM-DD' or full timestamps)
            $istDate = CarbonImmutable::parse($r->slot_date, 'Asia/Kolkata');
            if ($istHour <= 6) {
                $istDate = $istDate->addDay();
            }

            return [
                'id' => $r->id,
                'strip_id' => $r->strip_id,
                'slot_date' => $r->slot_date,
                'hour_24' => $r->hour_24,
                'role' => $r->role,
                'status' => $r->status,
                'committed_doctor_id' => $r->committed_doctor_id,
                'ist_hour' => $istHour,
                'ist_datetime' => $istDate->setTime($istHour, 0)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'doctor_id' => $doctorId,
            'date'      => $date,
            'day'       => $normalizedDay,
            'tz'        => $tz,
            'count'     => $mapped->count(),
            'slots'     => $mapped,
        ]);
    }

    private function normalizeDayOfWeek(string $day): ?string
    {
        $normalized = strtolower(trim($day));
        $valid = [
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
        ];

        return in_array($normalized, $valid, true) ? $normalized : null;
    }
}
