<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VideoCallingController extends Controller
{
    public function nearbyVets(Request $request)
    {
        return $this->buildNearbyResponse($request, 'vet');
    }

    public function nearbyDoctors(Request $request)
    {
        return $this->buildNearbyResponse($request, 'doctor');
    }

    private function buildNearbyResponse(Request $request, string $mode)
    {
        $userId = $request->query('user_id');
        $dateParam = $request->query('date');
        $dayInput = $request->query('day');

        $date = $dateParam !== null ? (string) $dateParam : now('Asia/Kolkata')->toDateString();

        $normalizedDay = null;
        if ($dayInput !== null && $dayInput !== '') {
            $normalizedDay = $this->normalizeDayOfWeek((string) $dayInput);
            if ($normalizedDay === null) {
                return response()->json(['status' => 'error', 'error' => 'day must be a valid weekday name'], 422);
            }
        }

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 422);
        }

        if ($date === '' && $normalizedDay === null) {
            return response()->json([
                'status' => 'success',
                'date'   => null,
                'day'    => null,
                'data'   => collect(),
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        // 1) User lat/lng lao
        $user = DB::table('users')
            ->select('latitude', 'longitude')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->latitude === null || $user->longitude === null || $user->latitude === '' || $user->longitude === '') {
            return response()->json(['status' => 'error', 'message' => 'User lat/long missing'], 422);
        }

        // 2) Numbers ensure karo
        $lat = (float) $user->latitude;
        $lng = (float) $user->longitude;
        $radiusKm = 100;

        // 3) Haversine with bindings
        $vets = DB::table('vet_registerations_temp')
            ->select('vet_registerations_temp.*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(vet_registerations_temp.lat)) *
                    cos(radians(vet_registerations_temp.lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(vet_registerations_temp.lat))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->whereNotNull('vet_registerations_temp.lat')
            ->whereNotNull('vet_registerations_temp.lng')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->get();

        if ($vets->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date === '' ? null : $date,
                'day'    => $normalizedDay,
                'data'   => $vets,
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $vetIds = $vets->pluck('id')->all();

        $doctors = DB::table('doctors')
            ->select(
                'id',
                'vet_registeration_id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'doctor_license',
                'doctor_document',
                'doctor_image',
                'toggle_availability'
            )
            ->whereIn('vet_registeration_id', $vetIds)
            ->get();

        if ($doctors->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date === '' ? null : $date,
                'day'    => $normalizedDay,
                'data'   => [],
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $doctorIds = $doctors->pluck('id')->all();

        $activeDoctorIds = DB::table('doctor_video_availability')
            ->whereIn('doctor_id', $doctorIds)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('doctor_id');

        if ($activeDoctorIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'date'   => $date === '' ? null : $date,
                'day'    => $normalizedDay,
                'data'   => [],
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $utcNightHours = array_merge(range(13, 23), range(0, 6));

        $busyQuery = DB::table('video_slots')
            ->whereIn('committed_doctor_id', $activeDoctorIds->all())
            ->whereIn('hour_24', $utcNightHours)
            ->whereIn('status', ['committed', 'in_progress', 'done']);

        if ($normalizedDay !== null) {
            $busyQuery->where('slot_day_of_week', $normalizedDay);
        } else {
            $busyQuery->where('slot_date', $date);
        }

        $busyDoctorIds = $busyQuery
            ->distinct()
            ->pluck('committed_doctor_id')
            ->all();

        $activeDoctorSet = array_fill_keys($activeDoctorIds->all(), true);
        $busyDoctorSet   = array_fill_keys($busyDoctorIds, true);

        $doctorsByVet = $doctors->groupBy('vet_registeration_id');
        $availableDoctorsByVet = [];

        $filteredVets = $vets->filter(function ($vet) use ($doctorsByVet, $activeDoctorSet, $busyDoctorSet, &$availableDoctorsByVet) {
            $doctorsForVet = $doctorsByVet->get($vet->id);

            if (!$doctorsForVet) {
                return false;
            }

            $qualified = collect($doctorsForVet)
                ->pluck('id')
                ->filter(function ($doctorId) use ($activeDoctorSet, $busyDoctorSet) {
                    if (!isset($activeDoctorSet[$doctorId])) {
                        return false;
                    }

                    if (isset($busyDoctorSet[$doctorId])) {
                        return false;
                    }

                    return true;
                })
                ->values()
                ->all();

            if (empty($qualified)) {
                return false;
            }

            $availableDoctorsByVet[$vet->id] = $qualified;

            return true;
        });

        $referralByVet = $this->buildReferralByVet($filteredVets);

        $dataPayload = $filteredVets
            ->values()
            ->map(function ($vet) use ($referralByVet) {
                $vet->referral_code = $referralByVet[$vet->id] ?? null;
                return $vet;
            });

        if ($mode === 'doctor') {
            $dataPayload = $this->transformToDoctorPayload($filteredVets, $availableDoctorsByVet, $doctors, $referralByVet);
        }

        return response()->json([
            'status' => 'success',
            'date'   => $date === '' ? null : $date,
            'day'    => $normalizedDay,
            'data'   => $dataPayload,
            'available_doctors_by_vet' => (object) $availableDoctorsByVet,
            'referral_by_vet' => (object) $referralByVet,
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

    private function transformToDoctorPayload(Collection $vets, array $availableDoctorsByVet, Collection $doctors, array $referralByVet): Collection
    {
        $vetIndex = $vets->keyBy('id');
        $doctorIndex = $doctors->keyBy('id');

        $payload = collect();

        foreach ($availableDoctorsByVet as $vetId => $doctorIds) {
            $vet = $vetIndex->get($vetId);
            if (!$vet) {
                continue;
            }

            $vetArray = json_decode(json_encode($vet), true);
            $clinicId = $vetArray['id'] ?? null;

            foreach ($doctorIds as $doctorId) {
                $doctor = $doctorIndex->get($doctorId);
                if (!$doctor) {
                    continue;
                }

                $entry = $vetArray;
                $entry['clinic_id'] = $clinicId;
                $entry['id'] = $doctor->id;
                $entry['referral_code'] = $referralByVet[$clinicId] ?? null;
                $entry['doctor'] = [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name,
                    'email' => $doctor->doctor_email,
                    'mobile' => $doctor->doctor_mobile,
                    'license' => $doctor->doctor_license,
                    'document' => $doctor->doctor_document,
                    'image' => $doctor->doctor_image,
                    'toggle_availability' => $doctor->toggle_availability,
                    'clinic_id' => $doctor->vet_registeration_id,
                ];

                $payload->push($entry);
            }
        }

        return $payload->values();
    }

    private function buildReferralByVet(Collection $vets): array
    {
        $map = [];

        foreach ($vets as $vet) {
            if (isset($vet->id)) {
                $map[$vet->id] = $this->referralCodeForClinic($vet);
            }
        }

        return $map;
    }

    private function referralCodeForClinic($clinic): string
    {
        $idSeed = isset($clinic->id) ? max(1, (int) $clinic->id) : 1;
        $base36 = strtoupper(str_pad(base_convert((string) $idSeed, 10, 36), 5, '0', STR_PAD_LEFT));

        $slug = $clinic->slug ?? $clinic->name ?? '';
        $slugFragment = strtoupper(Str::substr(Str::slug($slug), 0, 2));

        if ($slugFragment === '') {
            $slugFragment = 'CL';
        }

        return 'SN-'.$slugFragment.$base36;
    }
}
