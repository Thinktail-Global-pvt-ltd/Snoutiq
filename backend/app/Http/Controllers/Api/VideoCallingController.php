<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;

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

    public function nearbyPlusFeatured(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'user_id is required',
            ], 422);
        }

        $user = User::query()->select('id', 'last_vet_id')->find($userId);
        if (!$user) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // Return nearby results as individual doctors (not just clinics)
        $nearbyResponse = $this->buildNearbyResponse($request, 'doctor');
        if ($nearbyResponse->getStatusCode() !== 200) {
            return $nearbyResponse;
        }

        $nearby = $nearbyResponse->getData(true);
        $featured = $this->buildFeaturedData($user);

        return $this->jsonResponse([
            'status' => 'success',
            'date' => $nearby['date'] ?? null,
            'day' => $nearby['day'] ?? null,
            'nearby' => $nearby,
            'featured' => $featured,
        ]);
    }

    public function nearbyVetsByLocation(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $nearbyResponse = $this->buildNearbyResponse(
            $request,
            'doctor',
            (float) $validated['lat'],
            (float) $validated['lng']
        );

        if ($nearbyResponse->getStatusCode() !== 200) {
            return $nearbyResponse;
        }

        $nearby = $nearbyResponse->getData(true);
        $nearby['data'] = $this->compactNearbyDoctorPayload($nearby['data'] ?? []);
        unset($nearby['available_doctors_by_vet'], $nearby['referral_by_vet']);

        return $this->jsonResponse([
            'status' => 'success',
            'date' => $nearby['date'] ?? null,
            'day' => $nearby['day'] ?? null,
            'nearby' => $nearby,
        ]);
    }

    private function buildNearbyResponse(Request $request, string $mode, ?float $lat = null, ?float $lng = null)
    {
        $userId = $request->query('user_id');
        $dateParam = $request->query('date');
        $dayInput = $request->query('day');

        $date = $dateParam !== null ? (string) $dateParam : now('Asia/Kolkata')->toDateString();

        $normalizedDay = null;
        if ($dayInput !== null && $dayInput !== '') {
            $normalizedDay = $this->normalizeDayOfWeek((string) $dayInput);
            if ($normalizedDay === null) {
                return $this->jsonResponse(['status' => 'error', 'error' => 'day must be a valid weekday name'], 422);
            }
        }

        if (($lat === null || $lng === null) && !$userId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 422);
        }

        if ($date === '' && $normalizedDay === null) {
            return $this->jsonResponse([
                'status' => 'success',
                'date'   => null,
                'day'    => null,
                'data'   => collect(),
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        if ($lat === null || $lng === null) {
            // 1) User lat/lng lao
            $user = DB::table('users')
                ->select('latitude', 'longitude')
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
            }

            if ($user->latitude === null || $user->longitude === null || $user->latitude === '' || $user->longitude === '') {
                return $this->jsonResponse(['status' => 'error', 'message' => 'User lat/long missing'], 422);
            }

            // 2) Numbers ensure karo
            $lat = (float) $user->latitude;
            $lng = (float) $user->longitude;
        }

        $radiusKm = 100;
        $clinicLatExpr = "COALESCE(vet_registerations_temp.lat, CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[0]')) AS DECIMAL(10,7)) ELSE NULL END)";
        $clinicLngExpr = "COALESCE(vet_registerations_temp.lng, CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[1]')) AS DECIMAL(10,7)) ELSE NULL END)";

        // 3) Haversine with bindings
        $vets = DB::table('vet_registerations_temp')
            ->select('vet_registerations_temp.*')
            ->selectRaw("{$clinicLatExpr} AS resolved_lat")
            ->selectRaw("{$clinicLngExpr} AS resolved_lng")
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians({$clinicLatExpr})) *
                    cos(radians({$clinicLngExpr}) - radians(?)) +
                    sin(radians(?)) * sin(radians({$clinicLatExpr}))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->whereRaw("{$clinicLatExpr} IS NOT NULL")
            ->whereRaw("{$clinicLngExpr} IS NOT NULL")
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->get();

        $vets = $this->mergeFallbackGeocodedVets($vets, $lat, $lng, $radiusKm);

        if ($vets->isEmpty()) {
            return $this->jsonResponse([
                'status' => 'success',
                'date'   => $date === '' ? null : $date,
                'day'    => $normalizedDay,
                'data'   => $vets,
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $vetIds = $vets->pluck('id')->all();

        $doctors = DB::table('doctors')
            ->select('doctors.*')
            ->whereIn('vet_registeration_id', $vetIds)
            ->get();

        if ($doctors->isEmpty()) {
            if ($mode === 'vet') {
                $referralByVet = $this->buildReferralByVet($vets);
                $dataPayload = $vets
                    ->values()
                    ->map(function ($vet) use ($referralByVet) {
                        $vet->referral_code = $referralByVet[$vet->id] ?? null;
                        return $vet;
                    });

                return $this->jsonResponse([
                    'status' => 'success',
                    'date'   => $date === '' ? null : $date,
                    'day'    => $normalizedDay,
                    'data'   => $dataPayload,
                    'available_doctors_by_vet' => new \stdClass(),
                    'referral_by_vet' => (object) $referralByVet,
                ]);
            }

            return $this->jsonResponse([
                'status' => 'success',
                'date'   => $date === '' ? null : $date,
                'day'    => $normalizedDay,
                'data'   => [],
                'available_doctors_by_vet' => new \stdClass(),
            ]);
        }

        $doctorIds = $doctors->pluck('id')->all();
        $nowIst = now('Asia/Kolkata');
        $currentDayOfWeek = (int) $nowIst->dayOfWeek;
        $currentTime = $nowIst->format('H:i:s');

        $activeDoctorIds = DB::table('doctor_video_availability')
            ->whereIn('doctor_id', $doctorIds)
            ->where('is_active', 1)
            ->where('day_of_week', $currentDayOfWeek)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->where(function ($q) use ($currentTime) {
                // Available only when doctor is outside any configured break window.
                $q->whereNull('break_start')
                    ->orWhereNull('break_end')
                    ->orWhere('break_start', '>', $currentTime)
                    ->orWhere('break_end', '<=', $currentTime);
            })
            ->distinct()
            ->pluck('doctor_id');

        if ($activeDoctorIds->isEmpty()) {
            if ($mode === 'vet') {
                $referralByVet = $this->buildReferralByVet($vets);
                $dataPayload = $vets
                    ->values()
                    ->map(function ($vet) use ($referralByVet) {
                        $vet->referral_code = $referralByVet[$vet->id] ?? null;
                        return $vet;
                    });

                return $this->jsonResponse([
                    'status' => 'success',
                    'date'   => $date === '' ? null : $date,
                    'day'    => $normalizedDay,
                    'data'   => $dataPayload,
                    'available_doctors_by_vet' => new \stdClass(),
                    'referral_by_vet' => (object) $referralByVet,
                ]);
            }

            return $this->jsonResponse([
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

        $vetsForPayload = $mode === 'doctor' ? $filteredVets : $vets;
        $referralByVet = $this->buildReferralByVet($vetsForPayload);

        $dataPayload = $vetsForPayload
            ->values()
            ->map(function ($vet) use ($referralByVet) {
                $vet->referral_code = $referralByVet[$vet->id] ?? null;
                return $vet;
            });

        if ($mode === 'doctor') {
            $dataPayload = $this->transformToDoctorPayload($filteredVets, $availableDoctorsByVet, $doctors, $referralByVet);
        }

        return $this->jsonResponse([
            'status' => 'success',
            'date'   => $date === '' ? null : $date,
            'day'    => $normalizedDay,
            'data'   => $dataPayload,
            'available_doctors_by_vet' => (object) $availableDoctorsByVet,
            'referral_by_vet' => (object) $referralByVet,
        ]);
    }

    private function mergeFallbackGeocodedVets(Collection $vets, float $lat, float $lng, float $radiusKm): Collection
    {
        $existingIds = $vets->pluck('id')->map(fn ($id) => (int) $id)->all();

        $fallbackQuery = VetRegisterationTemp::query()
            ->whereHas('doctors');

        if (!empty($existingIds)) {
            $fallbackQuery->whereNotIn('id', $existingIds);
        }

        $fallbackClinics = $fallbackQuery
            ->where(function ($query) {
                $query->whereNull('lat')
                    ->orWhereNull('lng')
                    ->orWhere('lat', '')
                    ->orWhere('lng', '')
                    ->orWhereNull('coordinates')
                    ->orWhere('coordinates', '');
            })
            ->limit(500)
            ->get();

        if ($fallbackClinics->isEmpty()) {
            return $vets;
        }

        $resolved = $fallbackClinics
            ->map(function (VetRegisterationTemp $clinic) use ($lat, $lng, $radiusKm) {
                $coordinates = $this->resolveClinicCoordinates($clinic);
                if (!$coordinates) {
                    return null;
                }

                $distance = $this->distanceKm($lat, $lng, $coordinates['lat'], $coordinates['lng']);
                if ($distance === null || $distance > $radiusKm) {
                    return null;
                }

                $clinic->setAttribute('resolved_lat', $coordinates['lat']);
                $clinic->setAttribute('resolved_lng', $coordinates['lng']);
                $clinic->setAttribute('distance', $distance);

                $this->cacheClinicCoordinates($clinic, $coordinates['lat'], $coordinates['lng']);

                return (object) $clinic->getAttributes();
            })
            ->filter()
            ->values();

        if ($resolved->isEmpty()) {
            return $vets;
        }

        return $vets
            ->concat($resolved)
            ->unique(fn ($clinic) => (int) ($clinic->id ?? 0))
            ->sortBy(fn ($clinic) => (float) ($clinic->distance ?? INF))
            ->values();
    }

    private function resolveClinicCoordinates($clinic): ?array
    {
        $clinicLat = $this->numericOrNull($clinic->lat ?? null);
        $clinicLng = $this->numericOrNull($clinic->lng ?? null);

        if ($clinicLat !== null && $clinicLng !== null) {
            return ['lat' => $clinicLat, 'lng' => $clinicLng];
        }

        $fromCoordinates = $this->coordinatesFromJson($clinic->coordinates ?? null);
        if ($fromCoordinates) {
            return $fromCoordinates;
        }

        $fromGeoPincode = $this->coordinatesFromGeoPincodes($clinic);
        if ($fromGeoPincode) {
            return $fromGeoPincode;
        }

        return $this->coordinatesFromGoogle($clinic);
    }

    private function coordinatesFromJson($coordinates): ?array
    {
        if (!$coordinates) {
            return null;
        }

        if (is_string($coordinates)) {
            $decoded = json_decode($coordinates, true);
            if (is_array($decoded)) {
                $coordinates = $decoded;
            }
        }

        if (!is_array($coordinates)) {
            return null;
        }

        $lat = $this->numericOrNull($coordinates[0] ?? $coordinates['lat'] ?? $coordinates['latitude'] ?? null);
        $lng = $this->numericOrNull($coordinates[1] ?? $coordinates['lng'] ?? $coordinates['longitude'] ?? null);

        return $lat !== null && $lng !== null ? ['lat' => $lat, 'lng' => $lng] : null;
    }

    private function coordinatesFromGeoPincodes($clinic): ?array
    {
        if (!Schema::hasTable('geo_pincodes')) {
            return null;
        }

        $pincode = trim((string) ($clinic->pincode ?? ''));
        $city = trim((string) ($clinic->city ?? ''));
        $state = trim((string) ($clinic->state ?? ''));

        $query = DB::table('geo_pincodes')
            ->whereNotNull('lat')
            ->whereNotNull('lon');

        $geoRow = null;
        if ($pincode !== '') {
            $geoRow = (clone $query)->where('pincode', $pincode)->first(['lat', 'lon']);
        }

        if (!$geoRow && $city !== '') {
            $cityQuery = (clone $query)->where('city', $city);
            if ($state !== '' && Schema::hasColumn('geo_pincodes', 'state')) {
                $cityQuery->where('state', $state);
            }
            $geoRow = $cityQuery->first(['lat', 'lon']);
        }

        $lat = $this->numericOrNull($geoRow->lat ?? null);
        $lng = $this->numericOrNull($geoRow->lon ?? null);

        return $lat !== null && $lng !== null ? ['lat' => $lat, 'lng' => $lng] : null;
    }

    private function coordinatesFromGoogle($clinic): ?array
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY') ?: env('GOOGLE_API_KEY');
        if (!$apiKey) {
            return null;
        }

        $name = trim((string) ($clinic->name ?? ''));
        $address = trim((string) (($clinic->formatted_address ?? null) ?: ($clinic->address ?? '')));
        $city = trim((string) ($clinic->city ?? ''));
        $state = trim((string) ($clinic->state ?? ''));
        $pincode = trim((string) ($clinic->pincode ?? ''));

        $candidates = array_values(array_unique(array_filter([
            implode(', ', array_filter([$address, $city, $state, $pincode, 'India'])),
            implode(', ', array_filter([$pincode, $city, $state, 'India'])),
            implode(', ', array_filter([$name, $city, $state, 'India'])),
            implode(', ', array_filter([$city, $state, 'India'])),
        ])));

        foreach ($candidates as $queryLocation) {
            try {
                $response = Http::timeout(3)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $queryLocation,
                    'key' => $apiKey,
                ]);

                if (!$response->successful()) {
                    continue;
                }

                $location = $response->json('results.0.geometry.location');
                $lat = $this->numericOrNull($location['lat'] ?? null);
                $lng = $this->numericOrNull($location['lng'] ?? null);

                if ($lat !== null && $lng !== null) {
                    return ['lat' => $lat, 'lng' => $lng];
                }
            } catch (\Throwable $e) {
                Log::warning('nearby_vets_location_geocode_failed', [
                    'clinic_id' => $clinic->id ?? null,
                    'query' => $queryLocation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function cacheClinicCoordinates($clinic, float $lat, float $lng): void
    {
        $updates = [];

        if (Schema::hasColumn('vet_registerations_temp', 'lat') && $this->numericOrNull($clinic->lat ?? null) === null) {
            $updates['lat'] = $lat;
        }

        if (Schema::hasColumn('vet_registerations_temp', 'lng') && $this->numericOrNull($clinic->lng ?? null) === null) {
            $updates['lng'] = $lng;
        }

        if (Schema::hasColumn('vet_registerations_temp', 'coordinates') && empty($clinic->coordinates)) {
            $updates['coordinates'] = json_encode([$lat, $lng]);
        }

        if (empty($updates)) {
            return;
        }

        try {
            VetRegisterationTemp::query()
                ->where('id', $clinic->id)
                ->update($updates);
        } catch (\Throwable $e) {
            Log::warning('nearby_vets_location_coordinate_cache_failed', [
                'clinic_id' => $clinic->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): ?float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return round($earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
    }

    private function numericOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
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

            $vetArray = (array) $vet;
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
                $entry['doctor'] = (array) $doctor;

                $payload->push($entry);
            }
        }

        return $payload->values();
    }

    private function compactNearbyDoctorPayload(array $entries): array
    {
        return collect($entries)
            ->map(function (array $entry) {
                $doctor = $entry['doctor'] ?? [];
                if (!is_array($doctor)) {
                    $doctor = (array) $doctor;
                }

                $doctorImage = $doctor['doctor_image'] ?? $doctor['image'] ?? null;
                unset(
                    $doctor['doctor_image_blob'],
                    $doctor['password'],
                    $doctor['api_token'],
                    $doctor['api_token_hash'],
                    $doctor['remember_token']
                );

                if ($doctorImage !== null) {
                    $doctor['doctor_image'] = $doctorImage;
                }

                return [
                    'clinic_id' => $entry['clinic_id'] ?? $entry['id'] ?? null,
                    'name' => $entry['name'] ?? null,
                    'city' => $entry['city'] ?? null,
                    'address' => $entry['formatted_address'] ?? $entry['address'] ?? null,
                    'distance' => isset($entry['distance']) ? round((float) $entry['distance'], 2) : null,
                    'doctor' => $doctor,
                ];
            })
            ->values()
            ->all();
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

    private function buildFeaturedData(User $user): array
    {
        if (empty($user->last_vet_id)) {
            return [
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'last_vet_id' => null,
                    'clinic' => null,
                    'doctors' => [],
                ],
            ];
        }

        $clinic = VetRegisterationTemp::with('doctors')->find($user->last_vet_id);

        if (!$clinic) {
            return [
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'last_vet_id' => $user->last_vet_id,
                    'clinic' => null,
                    'doctors' => [],
                ],
            ];
        }

        $clinicData = [
            'id' => $clinic->id,
            'name' => $clinic->name,
            'slug' => $clinic->slug,
            'city' => $clinic->city,
            'address' => $clinic->formatted_address ?? $clinic->address,
            'phone' => $clinic->mobile,
            'image' => $clinic->image,
        ];

        $doctorsData = $clinic->doctors->map(function (Doctor $doc) {
            return $doc->toArray();
        })->values();

        return [
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'last_vet_id' => $user->last_vet_id,
                'clinic' => $clinicData,
                'doctors' => $doctorsData,
            ],
        ];
    }

    private function jsonResponse(array $payload, int $status = 200)
    {
        // Replace malformed bytes during JSON encoding instead of throwing.
        return response()->json(
            $payload,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
