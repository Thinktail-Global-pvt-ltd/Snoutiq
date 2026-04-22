<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GooglePlacesLookupService
{
    private const DEFAULT_RADIUS_METERS = 5000;

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

        $apiKey = trim((string) env('GOOGLE_API_KEY', ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => $normalizedType,
                'error' => 'Google Places API key is missing.',
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
