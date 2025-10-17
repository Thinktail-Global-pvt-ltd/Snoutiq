<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\StoreScheduleRequest;
use App\Models\DoctorWeeklyVideoSchedule;
use App\Models\DoctorWeeklyVideoScheduleDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DoctorVideoScheduleController extends Controller
{
    // GET /api/video/schedule/{doctor}
    public function show(int $doctor): JsonResponse
    {
        $sched = DoctorWeeklyVideoSchedule::query()->where('doctor_id', $doctor)->with('days')->first();
        if (!$sched) {
            return response()->json([
                'doctor_id' => $doctor,
                'avg_consult_minutes' => 20,
                'max_bookings_per_hour' => 3,
                'is_247' => false,
                'days' => [],
            ]);
        }

        // Convert stored UTC times to IST strings for UI
        $days = $sched->days->map(function (DoctorWeeklyVideoScheduleDay $d) {
            return [
                'dow' => (int) $d->dow,
                'active' => (bool) $d->active,
                'start_time' => $d->start_time ? $this->utcTimeToIstString($d->start_time) : null,
                'end_time' => $d->end_time ? $this->utcTimeToIstString($d->end_time) : null,
                'break_start_time' => $d->break_start_time ? $this->utcTimeToIstString($d->break_start_time) : null,
                'break_end_time' => $d->break_end_time ? $this->utcTimeToIstString($d->break_end_time) : null,
            ];
        })->values()->all();

        return response()->json([
            'doctor_id' => $doctor,
            'avg_consult_minutes' => (int) $sched->avg_consult_minutes,
            'max_bookings_per_hour' => (int) $sched->max_bookings_per_hour,
            'is_247' => (bool) $sched->is_247,
            'days' => $days,
        ]);
    }

    // POST /api/video/schedule/{doctor}
    public function storeOrUpdate(StoreScheduleRequest $request, int $doctor): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($doctor, $data) {
            $schedule = DoctorWeeklyVideoSchedule::query()->updateOrCreate(
                ['doctor_id' => $doctor],
                [
                    'avg_consult_minutes' => $data['avg_consult_minutes'],
                    'max_bookings_per_hour' => $data['max_bookings_per_hour'],
                    'is_247' => $data['is_247'],
                ]
            );

            // Rebuild days idempotently
            $map = [];
            foreach ($data['days'] as $day) {
                $dow = (int) $day['dow'];
                $active = (bool) $day['active'];

                if ($data['is_247']) {
                    $active = true;
                    $startIst = '00:00:00';
                    $endIst = '23:59:00';
                    $bsIst = null;
                    $beIst = null;
                } else {
                    $startIst = $day['start_time'] ? $this->normalizeHm($day['start_time']) : null;
                    $endIst   = $day['end_time'] ? $this->normalizeHm($day['end_time']) : null;
                    $bsIst    = $day['break_start_time'] ? $this->normalizeHm($day['break_start_time']) : null;
                    $beIst    = $day['break_end_time'] ? $this->normalizeHm($day['break_end_time']) : null;
                }

                $map[$dow] = [
                    'schedule_id' => $schedule->id,
                    'dow' => $dow,
                    'active' => $active,
                    'start_time' => $startIst ? $this->istHmToUtcTime($startIst) : null,
                    'end_time' => $endIst ? $this->istHmToUtcTime($endIst) : null,
                    'break_start_time' => $bsIst ? $this->istHmToUtcTime($bsIst) : null,
                    'break_end_time' => $beIst ? $this->istHmToUtcTime($beIst) : null,
                ];
            }

            // Upsert all 7
            foreach (range(0,6) as $dow) {
                $payload = $map[$dow] ?? [
                    'schedule_id' => $schedule->id,
                    'dow' => $dow,
                    'active' => false,
                    'start_time' => null,
                    'end_time' => null,
                    'break_start_time' => null,
                    'break_end_time' => null,
                ];
                DoctorWeeklyVideoScheduleDay::query()->updateOrCreate(
                    ['schedule_id' => $schedule->id, 'dow' => $dow],
                    $payload
                );
            }
        });

        return response()->json(['status' => 'ok']);
    }

    // POST /api/video/schedule/{doctor}/toggle-247
    public function toggle247(int $doctor): JsonResponse
    {
        $schedule = DoctorWeeklyVideoSchedule::query()->firstOrCreate(
            ['doctor_id' => $doctor],
            ['avg_consult_minutes' => 20, 'max_bookings_per_hour' => 3, 'is_247' => false]
        );
        $new = !$schedule->is_247;
        $schedule->is_247 = $new;
        $schedule->save();

        if ($new) {
            DB::transaction(function () use ($schedule) {
                foreach (range(0,6) as $dow) {
                    DoctorWeeklyVideoScheduleDay::query()->updateOrCreate(
                        ['schedule_id' => $schedule->id, 'dow' => $dow],
                        [
                            'active' => true,
                            'start_time' => $this->istHmToUtcTime('00:00:00'),
                            'end_time' => $this->istHmToUtcTime('23:59:00'),
                            'break_start_time' => null,
                            'break_end_time' => null,
                        ]
                    );
                }
            });
        }

        return response()->json(['is_247' => $new]);
    }

    private function normalizeHm(string $hm): string
    {
        // Accept HH:MM or HH:MM:SS and return HH:MM:00
        if (preg_match('/^\d{2}:\d{2}$/', $hm)) {
            return $hm . ':00';
        }
        return $hm;
    }

    private function istHmToUtcTime(string $hm): string
    {
        // Create an arbitrary date with IST time, then convert to UTC and return HH:MM:SS
        $ist = CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 ' . $hm, 'Asia/Kolkata');
        return $ist->setTimezone('UTC')->format('H:i:s');
    }

    private function utcTimeToIstString(string $hhmmss): string
    {
        $utc = CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 ' . $hhmmss, 'UTC');
        return $utc->setTimezone('Asia/Kolkata')->format('H:i:s');
    }
}

