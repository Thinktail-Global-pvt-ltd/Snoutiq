<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class GooglePlacesLookupService
{
    private const DEFAULT_RADIUS_METERS = 5000;
    private const DEFAULT_TIMEZONE = 'Asia/Kolkata';

    private const PLACE_DEFINITIONS = [
        'clinic' => [
            'label' => 'Clinic',
            'query' => 'veterinary clinic',
            'nearby_type' => 'veterinary_care',
            'keyword' => 'veterinary clinic',
        ],
        'hospital' => [
            'label' => 'Hospital',
            'query' => 'emergency veterinary hospital',
            'nearby_type' => 'veterinary_care',
            'keyword' => 'emergency veterinary hospital',
        ],
        'groomer' => [
            'label' => 'Groomer',
            'query' => 'pet groomer',
            'nearby_type' => null,
            'keyword' => 'pet grooming',
        ],
        'boarding' => [
            'label' => 'Boarding',
            'query' => 'pet boarding kennel',
            'nearby_type' => null,
            'keyword' => 'pet boarding kennel',
        ],
        'trainer' => [
            'label' => 'Trainer',
            'query' => 'dog trainer',
            'nearby_type' => null,
            'keyword' => 'dog trainer',
        ],
        'petshop' => [
            'label' => 'Pet Shop',
            'query' => 'pet shop',
            'nearby_type' => 'pet_store',
            'keyword' => 'pet shop',
        ],
        'dogpark' => [
            'label' => 'Dog Park',
            'query' => 'dog park',
            'nearby_type' => 'park',
            'keyword' => 'dog park',
        ],
    ];

    public function supportedTypes(): array
    {
        return array_keys(self::PLACE_DEFINITIONS);
    }

    public function normalizeType(?string $placeType): ?string
    {
        if ($placeType === null) {
            return null;
        }

        $normalizedType = $this->normalizePlaceType($placeType);
        return isset(self::PLACE_DEFINITIONS[$normalizedType]) ? $normalizedType : null;
    }

    public function search(
        string $placeType,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null,
        int $limit = 5
    ): array {
        $normalizedType = $this->normalizePlaceType($placeType);
        $definition = self::PLACE_DEFINITIONS[$normalizedType] ?? null;

        if (!$definition) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Unsupported place type.',
                'supported_types' => $this->supportedTypes(),
            ];
        }

        $apiKey = $this->resolveApiKey();
        if ($apiKey === null) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Google Places API key is missing. Set GOOGLE_API_KEY or GOOGLE_MAPS_API_KEY.',
                'supported_types' => $this->supportedTypes(),
            ];
        }

        $cleanLocation = $this->cleanText($location);
        $limit = max(1, min($limit, 10));
        $hasCoordinates = $latitude !== null && $longitude !== null;

        if (!$hasCoordinates && $cleanLocation === null) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Location or coordinates are required.',
                'requires_location' => true,
                'supported_types' => $this->supportedTypes(),
            ];
        }

        $response = $hasCoordinates
            ? $this->nearbySearch($definition, (float) $latitude, (float) $longitude, $apiKey)
            : $this->textSearch($definition, (string) $cleanLocation, $apiKey);

        if (!$response->successful()) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Google Places lookup failed.',
                'supported_types' => $this->supportedTypes(),
            ];
        }

        $payload = $response->json();
        $status = strtoupper((string) ($payload['status'] ?? 'UNKNOWN_ERROR'));

        if (!in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Google Places returned ' . $status . '.',
                'google_status' => $status,
                'supported_types' => $this->supportedTypes(),
            ];
        }

        $results = array_slice((array) ($payload['results'] ?? []), 0, $limit);
        $places = [];

        foreach ($results as $result) {
            $mapped = $this->mapPlace($result, $latitude, $longitude);
            if ($mapped !== null) {
                $places[] = $mapped;
            }
        }

        $originLabel = $cleanLocation;
        if ($originLabel === null && $hasCoordinates) {
            $originLabel = sprintf('%.5f, %.5f', (float) $latitude, (float) $longitude);
        }

        return [
            'success' => true,
            'kind' => 'nearby_places',
            'type' => $normalizedType,
            'label' => $definition['label'],
            'query' => $definition['query'],
            'location' => $originLabel,
            'count' => count($places),
            'places' => $places,
            'supported_types' => $this->supportedTypes(),
            'source' => 'google_places',
            'note' => count($places) === 0
                ? sprintf('No nearby %s results found.', strtolower($definition['label']))
                : null,
        ];
    }

    public function placeDetails(string $placeId): array
    {
        $apiKey = $this->resolveApiKey();
        if ($apiKey === null) {
            return [
                'success' => false,
                'kind' => 'place_details',
                'place_id' => $placeId,
                'error' => 'Google Places API key is missing. Set GOOGLE_API_KEY or GOOGLE_MAPS_API_KEY.',
            ];
        }

        $response = Http::timeout(10)
            ->retry(1, 200)
            ->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => implode(',', [
                    'place_id',
                    'name',
                    'formatted_address',
                    'formatted_phone_number',
                    'website',
                    'business_status',
                    'geometry',
                    'rating',
                    'user_ratings_total',
                    'regular_opening_hours',
                    'current_opening_hours',
                    'opening_hours',
                    'utc_offset_minutes',
                ]),
                'key' => $apiKey,
            ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'kind' => 'place_details',
                'place_id' => $placeId,
                'error' => 'Google Place Details lookup failed.',
            ];
        }

        $payload = $response->json();
        $status = strtoupper((string) ($payload['status'] ?? 'UNKNOWN_ERROR'));
        if ($status !== 'OK') {
            return [
                'success' => false,
                'kind' => 'place_details',
                'place_id' => $placeId,
                'error' => 'Google Place Details returned ' . $status . '.',
                'google_status' => $status,
            ];
        }

        $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];
        $geometry = is_array($result['geometry']['location'] ?? null) ? $result['geometry']['location'] : [];

        return [
            'success' => true,
            'kind' => 'place_details',
            'place_id' => $placeId,
            'place' => [
                'place_id' => $placeId,
                'name' => trim((string) ($result['name'] ?? '')),
                'address' => trim((string) ($result['formatted_address'] ?? '')),
                'phone' => trim((string) ($result['formatted_phone_number'] ?? '')) ?: null,
                'website' => trim((string) ($result['website'] ?? '')) ?: null,
                'rating' => isset($result['rating']) && is_numeric($result['rating']) ? (float) $result['rating'] : null,
                'user_ratings_total' => isset($result['user_ratings_total']) && is_numeric($result['user_ratings_total'])
                    ? (int) $result['user_ratings_total']
                    : null,
                'business_status' => $result['business_status'] ?? null,
                'maps_link' => 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode($placeId),
                'latitude' => isset($geometry['lat']) && is_numeric($geometry['lat']) ? (float) $geometry['lat'] : null,
                'longitude' => isset($geometry['lng']) && is_numeric($geometry['lng']) ? (float) $geometry['lng'] : null,
                'weekday_text' => data_get($result, 'regular_opening_hours.weekday_text')
                    ?? data_get($result, 'current_opening_hours.weekday_text')
                    ?? data_get($result, 'opening_hours.weekday_text')
                    ?? [],
                'opening_periods' => data_get($result, 'regular_opening_hours.periods')
                    ?? data_get($result, 'current_opening_hours.periods')
                    ?? data_get($result, 'opening_hours.periods')
                    ?? [],
                'utc_offset_minutes' => isset($result['utc_offset_minutes']) && is_numeric($result['utc_offset_minutes'])
                    ? (int) $result['utc_offset_minutes']
                    : null,
            ],
        ];
    }

    public function suggestedSlotsForPlace(
        string $placeId,
        ?string $startDate = null,
        int $days = 3,
        int $durationMinutes = 60,
        int $limit = 12
    ): array {
        $details = $this->placeDetails($placeId);
        if (($details['success'] ?? false) !== true) {
            return array_merge($details, [
                'kind' => 'available_slots',
            ]);
        }

        $place = $details['place'] ?? [];
        $periods = is_array($place['opening_periods'] ?? null) ? $place['opening_periods'] : [];
        $slots = $this->generateSlotsFromPeriods($placeId, $periods, $startDate, $days, $durationMinutes, $limit);
        $slotSource = 'google_opening_hours';

        if ($slots === []) {
            $slotSource = 'fallback_business_hours';
            $slots = $this->fallbackSuggestedSlots($placeId, $startDate, $days, $limit);
        }

        return [
            'success' => true,
            'kind' => 'available_slots',
            'place_id' => $placeId,
            'slot_source' => $slotSource,
            'place' => $place,
            'count' => count($slots),
            'slots' => $slots,
        ];
    }

    private function nearbySearch(array $definition, float $latitude, float $longitude, string $apiKey)
    {
        $params = [
            'location' => sprintf('%.7F,%.7F', $latitude, $longitude),
            'radius' => self::DEFAULT_RADIUS_METERS,
            'key' => $apiKey,
        ];

        if (!empty($definition['nearby_type'])) {
            $params['type'] = $definition['nearby_type'];
        }
        if (!empty($definition['keyword'])) {
            $params['keyword'] = $definition['keyword'];
        }

        return Http::timeout(10)
            ->retry(1, 200)
            ->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', $params);
    }

    private function textSearch(array $definition, string $location, string $apiKey)
    {
        return Http::timeout(10)
            ->retry(1, 200)
            ->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => trim($definition['query'] . ' near ' . $location),
                'key' => $apiKey,
            ]);
    }

    private function mapPlace(array $place, ?float $originLatitude, ?float $originLongitude): ?array
    {
        $name = trim((string) ($place['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $openingHours = is_array($place['opening_hours'] ?? null) ? $place['opening_hours'] : [];
        $geometry = is_array($place['geometry']['location'] ?? null) ? $place['geometry']['location'] : [];
        $placeLatitude = isset($geometry['lat']) && is_numeric($geometry['lat']) ? (float) $geometry['lat'] : null;
        $placeLongitude = isset($geometry['lng']) && is_numeric($geometry['lng']) ? (float) $geometry['lng'] : null;

        return [
            'name' => $name,
            'address' => trim((string) ($place['formatted_address'] ?? $place['vicinity'] ?? '')),
            'rating' => isset($place['rating']) && is_numeric($place['rating']) ? (float) $place['rating'] : null,
            'user_ratings_total' => isset($place['user_ratings_total']) && is_numeric($place['user_ratings_total'])
                ? (int) $place['user_ratings_total']
                : null,
            'open_now' => array_key_exists('open_now', $openingHours)
                ? (bool) $openingHours['open_now']
                : null,
            'place_id' => $place['place_id'] ?? null,
            'maps_link' => !empty($place['place_id'])
                ? 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode((string) $place['place_id'])
                : null,
            'business_status' => $place['business_status'] ?? null,
            'latitude' => $placeLatitude !== null ? round($placeLatitude, 7) : null,
            'longitude' => $placeLongitude !== null ? round($placeLongitude, 7) : null,
            'distance_km' => $this->distanceKm($originLatitude, $originLongitude, $placeLatitude, $placeLongitude),
        ];
    }

    private function normalizePlaceType(string $placeType): string
    {
        $normalized = strtolower(trim($placeType));
        $normalized = preg_replace('/[^a-z]/', '', $normalized) ?? '';

        return match ($normalized) {
            'vet', 'vets', 'vetclinic', 'veterinary', 'veterinaryclinic', 'clinic', 'clinics', 'doctor', 'doctors' => 'clinic',
            'hospital', 'hospitals', 'emergencyhospital', 'animalhospital' => 'hospital',
            'groomer', 'groomers', 'grooming', 'petgroomer' => 'groomer',
            'boarding', 'boardings', 'kennel', 'kennels', 'petboarding' => 'boarding',
            'trainer', 'trainers', 'training', 'dogtrainer' => 'trainer',
            'petshop', 'petshops', 'petstore', 'petstores', 'store' => 'petshop',
            'dogpark', 'dogparks', 'park', 'parks' => 'dogpark',
            default => $normalized,
        };
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim($value);
        return $clean === '' ? null : $clean;
    }

    private function resolveApiKey(): ?string
    {
        $apiKey = trim((string) (env('GOOGLE_API_KEY', env('GOOGLE_MAPS_API_KEY', ''))));

        if ($apiKey === '' || $apiKey === 'your_google_maps_api_key_here') {
            return null;
        }

        return $apiKey;
    }

    private function generateSlotsFromPeriods(
        string $placeId,
        array $periods,
        ?string $startDate,
        int $days,
        int $durationMinutes,
        int $limit
    ): array {
        if ($periods === []) {
            return [];
        }

        $start = $startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
            ? Carbon::createFromFormat('Y-m-d', $startDate, self::DEFAULT_TIMEZONE)
            : now(self::DEFAULT_TIMEZONE)->startOfDay();

        $days = max(1, min($days, 7));
        $durationMinutes = max(15, min($durationMinutes, 180));
        $cutoff = now(self::DEFAULT_TIMEZONE)->addMinutes(30);
        $slots = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $dayOfWeek = $date->dayOfWeek;

            foreach ($periods as $period) {
                $openDay = isset($period['open']['day']) && is_numeric($period['open']['day']) ? (int) $period['open']['day'] : null;
                $closeDay = isset($period['close']['day']) && is_numeric($period['close']['day']) ? (int) $period['close']['day'] : $openDay;
                $openTime = $period['open']['time'] ?? null;
                $closeTime = $period['close']['time'] ?? null;

                if ($openDay === null || $openTime === null || $closeTime === null) {
                    continue;
                }
                if ($openDay !== $dayOfWeek || $closeDay !== $dayOfWeek) {
                    continue;
                }

                $openAt = $this->buildLocalDateTimeFromGoogleTime($date, (string) $openTime);
                $closeAt = $this->buildLocalDateTimeFromGoogleTime($date, (string) $closeTime);
                if (!$openAt || !$closeAt || $closeAt->lessThanOrEqualTo($openAt)) {
                    continue;
                }

                $cursor = $openAt->copy();
                while ($cursor->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($closeAt)) {
                    if ($cursor->greaterThanOrEqualTo($cutoff)) {
                        $time = $cursor->format('H:i');
                        $endTime = $cursor->copy()->addMinutes($durationMinutes)->format('H:i');
                        $slots[] = [
                            'slot_id' => sprintf('clinic:%s:%s:%s', $placeId, $date->toDateString(), $time),
                            'consultation_type' => 'clinic',
                            'date' => $date->toDateString(),
                            'time' => $time,
                            'end_time' => $endTime,
                            'time_window' => $time . ' - ' . $endTime,
                            'slot_source' => 'google_opening_hours',
                        ];
                    }
                    $cursor->addMinutes($durationMinutes);
                    if (count($slots) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return $slots;
    }

    private function fallbackSuggestedSlots(string $placeId, ?string $startDate, int $days, int $limit): array
    {
        $start = $startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
            ? Carbon::createFromFormat('Y-m-d', $startDate, self::DEFAULT_TIMEZONE)
            : now(self::DEFAULT_TIMEZONE)->startOfDay();

        $templates = ['10:00', '12:00', '15:00', '17:00'];
        $slots = [];

        for ($offset = 0; $offset < max(1, min($days, 7)); $offset++) {
            $date = $start->copy()->addDays($offset)->toDateString();
            foreach ($templates as $time) {
                $end = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, self::DEFAULT_TIMEZONE)
                    ->addHour()
                    ->format('H:i');
                $slots[] = [
                    'slot_id' => sprintf('clinic:%s:%s:%s', $placeId, $date, $time),
                    'consultation_type' => 'clinic',
                    'date' => $date,
                    'time' => $time,
                    'end_time' => $end,
                    'time_window' => $time . ' - ' . $end,
                    'slot_source' => 'fallback_business_hours',
                ];

                if (count($slots) >= $limit) {
                    break 2;
                }
            }
        }

        return $slots;
    }

    private function buildLocalDateTimeFromGoogleTime(Carbon $date, string $hhmm): ?Carbon
    {
        if (!preg_match('/^\d{4}$/', $hhmm)) {
            return null;
        }

        $hours = (int) substr($hhmm, 0, 2);
        $minutes = (int) substr($hhmm, 2, 2);

        return $date->copy()->setTime($hours, $minutes, 0);
    }

    private function distanceKm(?float $originLatitude, ?float $originLongitude, ?float $placeLatitude, ?float $placeLongitude): ?float
    {
        if ($originLatitude === null || $originLongitude === null || $placeLatitude === null || $placeLongitude === null) {
            return null;
        }

        $earthRadiusKm = 6371.0;

        $deltaLatitude = deg2rad($placeLatitude - $originLatitude);
        $deltaLongitude = deg2rad($placeLongitude - $originLongitude);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($originLatitude))
            * cos(deg2rad($placeLatitude))
            * sin($deltaLongitude / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}
