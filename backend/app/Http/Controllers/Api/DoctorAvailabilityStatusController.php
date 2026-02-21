<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorAvailabilityStatusController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'timezone' => ['nullable', 'string', 'max:100'],
            'clinic_id' => ['nullable', 'integer'],
            'exported_from_excell' => ['nullable', 'boolean'],
        ]);

        $timezone = (string) ($validated['timezone'] ?? config('app.timezone', 'UTC'));
        try {
            $now = Carbon::now($timezone);
        } catch (\Throwable $e) {
            $timezone = (string) config('app.timezone', 'UTC');
            $now = Carbon::now($timezone);
        }

        $query = Doctor::query();

        if (array_key_exists('clinic_id', $validated) && $validated['clinic_id'] !== null) {
            $query->where('vet_registeration_id', (int) $validated['clinic_id']);
        }

        if (array_key_exists('exported_from_excell', $validated) && $validated['exported_from_excell'] !== null) {
            $wantsExported = (bool) $validated['exported_from_excell'];
            if ($wantsExported) {
                $query->where(function ($q) {
                    $q->where('exported_from_excell', 1)
                        ->orWhere('exported_from_excell', '1');
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('exported_from_excell')
                        ->orWhere('exported_from_excell', 0)
                        ->orWhere('exported_from_excell', '0');
                });
            }
        }

        $doctors = $query
            ->orderBy('id')
            ->get([
                'id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'vet_registeration_id',
                'exported_from_excell',
                'break_do_not_disturb_time_example_2_4_pm',
            ]);

        $onlineDoctors = [];
        $offlineDoctors = [];

        foreach ($doctors as $doctor) {
            $breakWindows = $this->decodeBreakWindows($doctor->break_do_not_disturb_time_example_2_4_pm);
            [$isOffline, $activeBreakRange] = $this->isDoctorOfflineNow($breakWindows, $now);

            $payload = [
                'id' => $doctor->id,
                'doctor_name' => $doctor->doctor_name,
                'doctor_email' => $doctor->doctor_email,
                'doctor_mobile' => $doctor->doctor_mobile,
                'clinic_id' => $doctor->vet_registeration_id,
                'exported_from_excell' => $doctor->exported_from_excell,
                'break_do_not_disturb_time_example_2_4_pm' => $breakWindows,
                'status' => $isOffline ? 'offline' : 'online',
                'is_offline' => $isOffline,
                'active_break_range' => $activeBreakRange,
            ];

            if ($isOffline) {
                $offlineDoctors[] = $payload;
            } else {
                $onlineDoctors[] = $payload;
            }
        }

        return response()->json([
            'success' => true,
            'generated_at' => $now->toIso8601String(),
            'timezone' => $timezone,
            'current_time' => $now->format('h:i A'),
            'counts' => [
                'total_doctors' => count($onlineDoctors) + count($offlineDoctors),
                'online_doctors' => count($onlineDoctors),
                'offline_doctors' => count($offlineDoctors),
            ],
            'online_doctors' => $onlineDoctors,
            'offline_doctors' => $offlineDoctors,
        ]);
    }

    private function decodeBreakWindows($rawValue): array
    {
        if (is_array($rawValue)) {
            return $this->cleanStringArray($rawValue);
        }

        if ($rawValue === null) {
            return [];
        }

        $rawString = trim((string) $rawValue);
        if ($rawString === '') {
            return [];
        }

        $decoded = json_decode($rawString, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->cleanStringArray($decoded);
        }

        return [$rawString];
    }

    private function cleanStringArray(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value !== '') {
                $result[] = $value;
            }
        }

        return array_values($result);
    }

    private function isDoctorOfflineNow(array $breakWindows, Carbon $now): array
    {
        if (empty($breakWindows)) {
            return [false, null];
        }

        $currentMinute = ((int) $now->format('H') * 60) + (int) $now->format('i');

        foreach ($breakWindows as $rawRange) {
            if ($this->isNoBreakMarker($rawRange)) {
                return [false, null];
            }

            $parsed = $this->parseBreakRange($rawRange);
            if (!$parsed) {
                continue;
            }

            [$startMinute, $endMinute] = $parsed;
            if ($this->isCurrentTimeInsideRange($currentMinute, $startMinute, $endMinute)) {
                return [true, $rawRange];
            }
        }

        return [false, null];
    }

    private function isNoBreakMarker(string $value): bool
    {
        return strtolower(trim($value)) === 'no';
    }

    private function parseBreakRange(string $value): ?array
    {
        $normalized = trim(str_replace(['–', '—'], '-', $value));
        if ($normalized === '') {
            return null;
        }

        $parts = preg_split('/\s*(?:-|to)\s*/i', $normalized, 2);
        if (!is_array($parts) || count($parts) !== 2) {
            return null;
        }

        $start = trim($parts[0]);
        $end = trim($parts[1]);
        if ($start === '' || $end === '') {
            return null;
        }

        $startHasMeridiem = $this->hasMeridiem($start);
        $endHasMeridiem = $this->hasMeridiem($end);

        if (!$startHasMeridiem && $endHasMeridiem) {
            $start .= ' '.$this->extractMeridiem($end);
        } elseif (!$endHasMeridiem && $startHasMeridiem) {
            $end .= ' '.$this->extractMeridiem($start);
        }

        $startMinute = $this->parseTimeToMinutes($start);
        $endMinute = $this->parseTimeToMinutes($end);

        if ($startMinute === null || $endMinute === null) {
            return null;
        }

        return [$startMinute, $endMinute];
    }

    private function hasMeridiem(string $value): bool
    {
        return (bool) preg_match('/\b(?:AM|PM)\b/i', $value);
    }

    private function extractMeridiem(string $value): string
    {
        if (preg_match('/\b(AM|PM)\b/i', $value, $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    private function parseTimeToMinutes(string $value): ?int
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['A.M.', 'P.M.'], ['AM', 'PM'], $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        $formats = [
            'g:i A',
            'g A',
            'h:i A',
            'h A',
            'H:i',
            'G:i',
            'H',
            'G',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $normalized);
            } catch (\Throwable $e) {
                $parsed = false;
            }

            if ($parsed !== false) {
                return ((int) $parsed->format('H') * 60) + (int) $parsed->format('i');
            }
        }

        return null;
    }

    private function isCurrentTimeInsideRange(int $currentMinute, int $startMinute, int $endMinute): bool
    {
        if ($startMinute === $endMinute) {
            return true;
        }

        if ($startMinute < $endMinute) {
            return $currentMinute >= $startMinute && $currentMinute < $endMinute;
        }

        // Overnight range e.g. 10:00 PM - 02:00 AM
        return $currentMinute >= $startMinute || $currentMinute < $endMinute;
    }
}

