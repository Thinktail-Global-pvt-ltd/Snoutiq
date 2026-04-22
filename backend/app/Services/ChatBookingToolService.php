<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChatBookingToolService
{
    private const DEFAULT_TIMEZONE = 'Asia/Kolkata';

    private const PRICE_MAP = [
        'video' => 499.00,
        'clinic' => 350.00,
        'home' => 999.00,
    ];

    public function __construct(
        private readonly GooglePlacesLookupService $places
    ) {
    }

    public function supportedConsultationTypes(): array
    {
        return ['video', 'clinic', 'home'];
    }

    public function normalizeConsultationType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z_]/', '', $normalized) ?? '';

        return match ($normalized) {
            'video', 'videoconsult', 'video_consult', 'videocall', 'video_call' => 'video',
            'clinic', 'inclinic', 'in_clinic', 'physicalexam' => 'clinic',
            'home', 'homevisit', 'home_visit', 'vetathome', 'vet_at_home' => 'home',
            default => null,
        };
    }

    public function attachSuggestedSlotsToPlaces(array $places, string $placeType, ?string $startDate = null, int $days = 3, int $limitPerPlace = 4): array
    {
        $bookablePlaceTypes = ['clinic', 'hospital', 'groomer', 'boarding', 'trainer', 'petshop', 'dogpark'];
        if (!in_array($placeType, $bookablePlaceTypes, true)) {
            return $places;
        }

        $enhanced = [];
        foreach (array_values($places) as $index => $place) {
            if (!is_array($place)) {
                $enhanced[] = $place;
                continue;
            }

            if ($index >= 3 || empty($place['place_id'])) {
                $place['suggested_slots'] = [];
                $place['slot_source'] = null;
                $enhanced[] = $place;
                continue;
            }

            $slotResult = $this->places->suggestedSlotsForPlace(
                (string) $place['place_id'],
                $startDate,
                $days,
                60,
                $limitPerPlace
            );

            $place['suggested_slots'] = $slotResult['slots'] ?? [];
            $place['slot_source'] = $slotResult['slot_source'] ?? null;
            $place['phone'] = $place['phone'] ?? ($slotResult['place']['phone'] ?? null);
            $place['website'] = $slotResult['place']['website'] ?? null;
            $enhanced[] = $place;
        }

        return $enhanced;
    }

    public function getAvailableSlots(array $payload): array
    {
        $consultationType = $this->normalizeConsultationType($payload['consultation_type'] ?? null);
        if ($consultationType === null) {
            return [
                'success' => false,
                'kind' => 'available_slots',
                'error' => 'consultation_type is required.',
                'supported_consultation_types' => $this->supportedConsultationTypes(),
            ];
        }

        return match ($consultationType) {
            'video' => $this->videoSlots($payload),
            'clinic' => $this->clinicSlots($payload),
            'home' => $this->homeVisitSlots($payload),
            default => [
                'success' => false,
                'kind' => 'available_slots',
                'error' => 'Unsupported consultation_type.',
                'supported_consultation_types' => $this->supportedConsultationTypes(),
            ],
        };
    }

    public function bookAppointment(array $payload): array
    {
        $consultationType = $this->normalizeConsultationType($payload['consultation_type'] ?? null);
        if ($consultationType === null) {
            return [
                'success' => false,
                'kind' => 'booking_confirmation',
                'error' => 'consultation_type is required.',
                'supported_consultation_types' => $this->supportedConsultationTypes(),
            ];
        }

        if (!Schema::hasTable('chat_service_bookings')) {
            return [
                'success' => false,
                'kind' => 'booking_confirmation',
                'error' => 'chat_service_bookings table is missing. Run the migration first.',
            ];
        }

        $slot = $this->resolveSelectedSlot($payload, $consultationType);
        if (!$slot['valid']) {
            return [
                'success' => false,
                'kind' => 'booking_confirmation',
                'consultation_type' => $consultationType,
                'error' => $slot['error'],
            ];
        }

        $placeData = $this->resolveBookingPlaceData($payload, $consultationType, $slot);
        $price = (float) ($payload['price'] ?? self::PRICE_MAP[$consultationType] ?? 0);
        $scheduledDate = $slot['date'];
        $scheduledTime = $slot['time'];
        $scheduledFor = $scheduledDate && $scheduledTime
            ? Carbon::createFromFormat('Y-m-d H:i', $scheduledDate . ' ' . $scheduledTime, self::DEFAULT_TIMEZONE)
            : null;

        $reference = 'CSB-' . now(self::DEFAULT_TIMEZONE)->format('Ymd') . '-' . strtoupper(Str::random(6));

        $insert = [
            'booking_reference' => $reference,
            'session_id' => $this->cleanText($payload['session_id'] ?? $payload['context_token'] ?? $payload['chat_room_token'] ?? null),
            'user_id' => $this->nullableInteger($payload['user_id'] ?? null),
            'pet_id' => $this->nullableInteger($payload['pet_id'] ?? null),
            'consultation_type' => $consultationType,
            'booking_status' => 'confirmed',
            'slot_id' => $slot['slot_id'],
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime ? $scheduledTime . ':00' : null,
            'scheduled_for' => $scheduledFor?->format('Y-m-d H:i:s'),
            'timezone' => self::DEFAULT_TIMEZONE,
            'doctor_id' => $this->nullableInteger($payload['doctor_id'] ?? $slot['doctor_id'] ?? null),
            'clinic_id' => $this->nullableInteger($payload['clinic_id'] ?? $slot['clinic_id'] ?? null),
            'external_place_id' => $placeData['place_id'] ?? null,
            'clinic_name' => $placeData['place_name'] ?? ($payload['clinic_name'] ?? null),
            'doctor_name' => $slot['doctor_name'] ?? ($payload['doctor_name'] ?? null),
            'address' => $placeData['address'] ?? ($payload['place_address'] ?? null),
            'phone' => $placeData['phone'] ?? null,
            'maps_link' => $placeData['maps_link'] ?? ($payload['maps_link'] ?? null),
            'price' => $price,
            'currency' => strtoupper(trim((string) ($payload['currency'] ?? 'INR'))) ?: 'INR',
            'source_tool' => 'book_appointment',
            'booking_payload' => json_encode([
                'payload' => $payload,
                'resolved_slot' => $slot,
                'place' => $placeData,
            ], JSON_UNESCAPED_UNICODE),
            'notes' => $this->cleanText($payload['notes'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $bookingId = DB::table('chat_service_bookings')->insertGetId($insert);

        return [
            'success' => true,
            'kind' => 'booking_confirmation',
            'consultation_type' => $consultationType,
            'booking_id' => (int) $bookingId,
            'booking_reference' => $reference,
            'booking_status' => 'confirmed',
            'slot_id' => $slot['slot_id'],
            'date' => $scheduledDate,
            'time' => $scheduledTime,
            'scheduled_for' => $scheduledFor?->toIso8601String(),
            'doctor_id' => $insert['doctor_id'],
            'doctor_name' => $insert['doctor_name'],
            'clinic_id' => $insert['clinic_id'],
            'clinic_name' => $insert['clinic_name'],
            'place_id' => $insert['external_place_id'],
            'address' => $insert['address'],
            'phone' => $insert['phone'],
            'maps_link' => $insert['maps_link'],
            'price' => $price,
            'currency' => $insert['currency'],
            'message' => $this->buildBookingMessage($consultationType, $insert['clinic_name'], $scheduledDate, $scheduledTime),
        ];
    }

    private function videoSlots(array $payload): array
    {
        $startDate = $this->resolveStartDate($payload);
        $days = $this->resolveDays($payload, 3, 7);
        $doctorId = $this->nullableInteger($payload['doctor_id'] ?? null);
        $clinicId = $this->nullableInteger($payload['clinic_id'] ?? null);

        $query = DB::table('doctors as d')
            ->join('doctor_video_availability as dva', 'd.id', '=', 'dva.doctor_id')
            ->leftJoin('vet_registerations_temp as v', 'v.id', '=', 'd.vet_registeration_id')
            ->where('dva.is_active', 1)
            ->select([
                'd.id',
                'd.doctor_name',
                'd.video_day_rate',
                'd.video_night_rate',
                'd.vet_registeration_id as clinic_id',
                'v.name as clinic_name',
            ])
            ->distinct();

        if ($doctorId !== null) {
            $query->where('d.id', $doctorId);
        }
        if ($clinicId !== null) {
            $query->where('d.vet_registeration_id', $clinicId);
        }

        $doctors = $query
            ->orderBy('d.id')
            ->limit(3)
            ->get();

        if ($doctors->isEmpty()) {
            return [
                'success' => false,
                'kind' => 'available_slots',
                'consultation_type' => 'video',
                'error' => 'No active video consultation schedule found.',
            ];
        }

        $slots = [];
        foreach ($doctors as $doctor) {
            $start = Carbon::createFromFormat('Y-m-d', $startDate, self::DEFAULT_TIMEZONE);
            for ($offset = 0; $offset < $days; $offset++) {
                $date = $start->copy()->addDays($offset)->toDateString();
                foreach ($this->buildVideoFreeSlotsForDoctorOnDate((int) $doctor->id, $date) as $time) {
                    $price = $this->videoPriceForTime($doctor, $time);
                    $slots[] = [
                        'slot_id' => sprintf('video:%d:%s:%s', (int) $doctor->id, $date, substr($time, 0, 5)),
                        'consultation_type' => 'video',
                        'date' => $date,
                        'time' => substr($time, 0, 5),
                        'doctor_id' => (int) $doctor->id,
                        'doctor_name' => $doctor->doctor_name,
                        'clinic_id' => $doctor->clinic_id ? (int) $doctor->clinic_id : null,
                        'clinic_name' => $doctor->clinic_name,
                        'price' => $price,
                        'currency' => 'INR',
                        'slot_source' => 'doctor_video_availability',
                    ];
                }
            }
        }

        $slots = array_slice($slots, 0, 18);

        return [
            'success' => true,
            'kind' => 'available_slots',
            'consultation_type' => 'video',
            'slot_source' => 'doctor_video_availability',
            'count' => count($slots),
            'price_from' => self::PRICE_MAP['video'],
            'slots' => $slots,
        ];
    }

    private function clinicSlots(array $payload): array
    {
        $startDate = $this->resolveStartDate($payload);
        $days = $this->resolveDays($payload, 3, 5);

        $placeId = $this->cleanText($payload['place_id'] ?? $payload['external_place_id'] ?? $payload['selected_place_id'] ?? null);
        if ($placeId === null) {
            $placeName = $this->cleanText($payload['place_name'] ?? $payload['clinic_name'] ?? null);
            $location = $this->cleanText($payload['location'] ?? $payload['pet_location'] ?? null);
            if ($placeName !== null && $location !== null) {
                $search = $this->places->search('clinic', $location, $payload['latitude'] ?? null, $payload['longitude'] ?? null, 5);
                if (($search['success'] ?? false) === true) {
                    foreach ((array) ($search['places'] ?? []) as $candidate) {
                        if (stripos((string) ($candidate['name'] ?? ''), $placeName) !== false) {
                            $placeId = $candidate['place_id'] ?? null;
                            break;
                        }
                    }
                    if ($placeId === null && !empty($search['places'][0]['place_id'])) {
                        $placeId = $search['places'][0]['place_id'];
                    }
                }
            }
        }

        if ($placeId === null) {
            return [
                'success' => false,
                'kind' => 'available_slots',
                'consultation_type' => 'clinic',
                'error' => 'place_id is required for clinic slots.',
            ];
        }

        $slotResult = $this->places->suggestedSlotsForPlace($placeId, $startDate, $days, 60, 12);
        if (($slotResult['success'] ?? false) !== true) {
            return array_merge($slotResult, [
                'kind' => 'available_slots',
                'consultation_type' => 'clinic',
            ]);
        }

        return [
            'success' => true,
            'kind' => 'available_slots',
            'consultation_type' => 'clinic',
            'slot_source' => $slotResult['slot_source'] ?? 'google_opening_hours',
            'place' => $slotResult['place'] ?? null,
            'count' => count($slotResult['slots'] ?? []),
            'price_from' => self::PRICE_MAP['clinic'],
            'slots' => $slotResult['slots'] ?? [],
        ];
    }

    private function homeVisitSlots(array $payload): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $this->resolveStartDate($payload), self::DEFAULT_TIMEZONE);
        $days = $this->resolveDays($payload, 3, 5);
        $windows = [
            ['10:00', '12:00'],
            ['12:00', '14:00'],
            ['15:00', '17:00'],
            ['18:00', '20:00'],
        ];

        $slots = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset)->toDateString();
            foreach ($windows as [$startTime, $endTime]) {
                $slots[] = [
                    'slot_id' => sprintf('home:%s:%s', $date, $startTime),
                    'consultation_type' => 'home',
                    'date' => $date,
                    'time' => $startTime,
                    'end_time' => $endTime,
                    'time_window' => $startTime . ' - ' . $endTime,
                    'price' => self::PRICE_MAP['home'],
                    'currency' => 'INR',
                    'slot_source' => 'generated_home_visit_windows',
                ];
            }
        }

        return [
            'success' => true,
            'kind' => 'available_slots',
            'consultation_type' => 'home',
            'slot_source' => 'generated_home_visit_windows',
            'count' => count($slots),
            'price_from' => self::PRICE_MAP['home'],
            'slots' => $slots,
        ];
    }

    private function buildVideoFreeSlotsForDoctorOnDate(int $doctorId, string $date): array
    {
        $dow = (int) date('w', strtotime($date));
        $rows = DB::table('doctor_video_availability')
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dow)
            ->where('is_active', 1)
            ->orderBy('start_time')
            ->get();

        $allSlots = [];
        foreach ($rows as $row) {
            $step = max(5, (int) ($row->avg_consultation_mins ?? 20));
            $start = ((int) substr((string) $row->start_time, 0, 2) * 60) + (int) substr((string) $row->start_time, 3, 2);
            $end = ((int) substr((string) $row->end_time, 0, 2) * 60) + (int) substr((string) $row->end_time, 3, 2);
            $breakStart = $row->break_start ? (((int) substr((string) $row->break_start, 0, 2) * 60) + (int) substr((string) $row->break_start, 3, 2)) : null;
            $breakEnd = $row->break_end ? (((int) substr((string) $row->break_end, 0, 2) * 60) + (int) substr((string) $row->break_end, 3, 2)) : null;

            for ($minute = $start; $minute + $step <= $end; $minute += $step) {
                if ($breakStart !== null && $breakEnd !== null && $minute >= $breakStart && $minute < $breakEnd) {
                    continue;
                }
                $hh = str_pad((string) intdiv($minute, 60), 2, '0', STR_PAD_LEFT);
                $mm = str_pad((string) ($minute % 60), 2, '0', STR_PAD_LEFT);
                $allSlots[] = $hh . ':' . $mm . ':00';
            }
        }

        $booked = $this->bookedVideoTimesForDoctorOnDate($doctorId, $date);

        return array_values(array_diff($allSlots, $booked));
    }

    private function bookedVideoTimesForDoctorOnDate(int $doctorId, string $date): array
    {
        $booked = [];

        if (Schema::hasTable('bookings')) {
            $rows = DB::table('bookings')
                ->where('assigned_doctor_id', $doctorId)
                ->whereDate('scheduled_for', $date)
                ->whereIn('service_type', ['video', 'video_consult'])
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->pluck('scheduled_for')
                ->all();

            foreach ($rows as $row) {
                $booked[] = date('H:i:00', strtotime((string) $row));
            }
        }

        if (Schema::hasTable('chat_service_bookings')) {
            $rows = DB::table('chat_service_bookings')
                ->where('doctor_id', $doctorId)
                ->where('consultation_type', 'video')
                ->whereDate('scheduled_for', $date)
                ->whereNotIn('booking_status', ['cancelled'])
                ->pluck('scheduled_for')
                ->all();

            foreach ($rows as $row) {
                $booked[] = date('H:i:00', strtotime((string) $row));
            }
        }

        return array_values(array_unique($booked));
    }

    private function videoPriceForTime(object $doctor, string $time): float
    {
        $hour = (int) substr($time, 0, 2);
        if ($hour >= 20 || $hour < 8) {
            return (float) ($doctor->video_night_rate ?? self::PRICE_MAP['video']);
        }

        return (float) ($doctor->video_day_rate ?? self::PRICE_MAP['video']);
    }

    private function resolveSelectedSlot(array $payload, string $consultationType): array
    {
        $slotId = $this->cleanText($payload['slot_id'] ?? null);
        $slotDate = $this->cleanText($payload['slot_date'] ?? $payload['date'] ?? null);
        $slotTime = $this->cleanText($payload['slot_time'] ?? $payload['time'] ?? null);

        if ($slotId !== null) {
            $parts = explode(':', $slotId);
            if (count($parts) >= 4) {
                return [
                    'valid' => true,
                    'slot_id' => $slotId,
                    'date' => $parts[2],
                    'time' => $parts[3],
                    'doctor_id' => $consultationType === 'video' ? (int) ($parts[1] ?? 0) : null,
                    'clinic_id' => null,
                    'doctor_name' => null,
                ];
            }
        }

        if ($slotDate === null || $slotTime === null) {
            return [
                'valid' => false,
                'error' => 'slot_id or slot_date + slot_time are required to book.',
            ];
        }

        $normalizedTime = preg_match('/^\d{2}:\d{2}$/', $slotTime) ? $slotTime : substr($slotTime, 0, 5);

        return [
            'valid' => true,
            'slot_id' => sprintf('%s:%s:%s', $consultationType, $slotDate, $normalizedTime),
            'date' => $slotDate,
            'time' => $normalizedTime,
            'doctor_id' => $this->nullableInteger($payload['doctor_id'] ?? null),
            'clinic_id' => $this->nullableInteger($payload['clinic_id'] ?? null),
            'doctor_name' => $this->cleanText($payload['doctor_name'] ?? null),
        ];
    }

    private function resolveBookingPlaceData(array $payload, string $consultationType, array $slot): array
    {
        if ($consultationType !== 'clinic') {
            return [
                'place_id' => $this->cleanText($payload['place_id'] ?? null),
                'place_name' => $this->cleanText($payload['clinic_name'] ?? null),
                'address' => $this->cleanText($payload['place_address'] ?? null),
                'phone' => $this->cleanText($payload['phone'] ?? null),
                'maps_link' => $this->cleanText($payload['maps_link'] ?? null),
            ];
        }

        $placeId = $this->cleanText($payload['place_id'] ?? $payload['external_place_id'] ?? null);
        if ($placeId !== null) {
            $details = $this->places->placeDetails($placeId);
            if (($details['success'] ?? false) === true) {
                return [
                    'place_id' => $placeId,
                    'place_name' => $details['place']['name'] ?? ($payload['clinic_name'] ?? null),
                    'address' => $details['place']['address'] ?? ($payload['place_address'] ?? null),
                    'phone' => $details['place']['phone'] ?? null,
                    'maps_link' => $details['place']['maps_link'] ?? ($payload['maps_link'] ?? null),
                ];
            }
        }

        return [
            'place_id' => $placeId,
            'place_name' => $this->cleanText($payload['clinic_name'] ?? $payload['place_name'] ?? null),
            'address' => $this->cleanText($payload['place_address'] ?? null),
            'phone' => $this->cleanText($payload['phone'] ?? null),
            'maps_link' => $this->cleanText($payload['maps_link'] ?? null),
        ];
    }

    private function buildBookingMessage(string $consultationType, ?string $clinicName, ?string $date, ?string $time): string
    {
        return match ($consultationType) {
            'video' => sprintf('Video consultation booked for %s at %s. The assigned vet will connect at the selected time.', $date ?? 'the selected date', $time ?? 'the selected time'),
            'home' => sprintf('Home vet visit booked for %s at %s. Keep your pet ready and reachable on phone.', $date ?? 'the selected date', $time ?? 'the selected time'),
            default => sprintf('Clinic booking confirmed%s for %s at %s.', $clinicName ? ' at ' . $clinicName : '', $date ?? 'the selected date', $time ?? 'the selected time'),
        };
    }

    private function resolveStartDate(array $payload): string
    {
        $date = $this->cleanText($payload['slot_date'] ?? $payload['date'] ?? $payload['start_date'] ?? null);
        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return now(self::DEFAULT_TIMEZONE)->toDateString();
    }

    private function resolveDays(array $payload, int $default = 3, int $max = 7): int
    {
        $days = isset($payload['days']) && is_numeric($payload['days']) ? (int) $payload['days'] : $default;
        $days = max(1, min($days, $max));

        return $days;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function cleanText(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $clean = trim((string) $value);
        return $clean === '' ? null : $clean;
    }
}
