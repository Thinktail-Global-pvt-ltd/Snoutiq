<?php

namespace App\Services;

use App\Models\Pet;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class RagSnouticSymptomService
{
    private const EXTERNAL_QUERY_URL = 'http://82.25.104.75:5050/query';

    public function defaultPayload(): array
    {
        return [
            'name' => 'Bruno',
            'species' => 'Dog',
            'breed' => 'Labrador',
            'age' => '3 years',
            'weight' => '28 kg',
            'sex' => 'Male',
            'vaccination_summary' => 'All core vaccines up to date',
            'medical_history' => 'No known issues',
            'query' => 'He is vomiting yellow foam and not eating since morning, kind of weak',
        ];
    }

    public function externalQueryUrl(): string
    {
        $configured = (string) config('services.snoutiq.symptom_query_url', '');

        return trim($configured) !== ''
            ? trim($configured)
            : self::EXTERNAL_QUERY_URL;
    }

    /**
     * @return array{pet_id:int,pet:array,payload:array,vaccination:mixed}|null
     */
    public function prefillPayloadByPetId(int $petId): ?array
    {
        if ($petId <= 0) {
            return null;
        }

        $pet = Pet::query()->find($petId);
        if (! $pet) {
            return null;
        }

        $vaccination = data_get($pet->dog_disease_payload, 'vaccination');

        return [
            'pet_id' => (int) $pet->id,
            'pet' => [
                'id' => (int) $pet->id,
                'name' => $pet->name,
                'breed' => $pet->breed,
                'species' => $this->extractSpecies($pet),
                'age' => $this->extractAge($pet),
                'weight' => $this->extractWeight($pet),
                'sex' => $this->extractSex($pet),
            ],
            'payload' => $this->buildPayloadFromPet($pet),
            'vaccination' => $vaccination,
        ];
    }

    public function normalizePayload(array $payload): array
    {
        $normalized = [];
        foreach ($this->defaultPayload() as $key => $_) {
            $value = Arr::get($payload, $key);
            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    /**
     * @return array{success:bool,status:int|null,response_data:mixed,error:?string}
     */
    public function queryExternal(array $payload, int $timeoutSeconds = 10): array
    {
        try {
            $response = Http::timeout($timeoutSeconds)->post($this->externalQueryUrl(), $payload);
            $responseData = $response->json();

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'response_data' => $responseData,
                    'error' => 'Unable to fetch symptom checker data right now.',
                ];
            }

            return [
                'success' => true,
                'status' => $response->status(),
                'response_data' => $responseData,
                'error' => null,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'status' => null,
                'response_data' => null,
                'error' => 'Failed to contact the symptom checker service.',
            ];
        }
    }

    private function buildPayloadFromPet(Pet $pet): array
    {
        $payload = $this->defaultPayload();

        $payload['name'] = $this->firstNonEmptyString([
            $pet->name,
            $payload['name'],
        ]);

        $payload['species'] = $this->firstNonEmptyString([
            $this->extractSpecies($pet),
            $payload['species'],
        ]);

        $payload['breed'] = $this->firstNonEmptyString([
            $pet->breed,
            $payload['breed'],
        ]);

        $payload['age'] = $this->firstNonEmptyString([
            $this->extractAge($pet),
            $payload['age'],
        ]);

        $payload['weight'] = $this->firstNonEmptyString([
            $this->extractWeight($pet),
            $payload['weight'],
        ]);

        $payload['sex'] = $this->firstNonEmptyString([
            $this->extractSex($pet),
            $payload['sex'],
        ]);

        $payload['vaccination_summary'] = $this->firstNonEmptyString([
            $this->extractVaccinationSummary($pet),
            $payload['vaccination_summary'],
        ]);

        $payload['medical_history'] = $this->firstNonEmptyString([
            $this->extractMedicalHistory($pet),
            $payload['medical_history'],
        ]);

        $payload['query'] = $this->firstNonEmptyString([
            $this->cleanText($pet->reported_symptom ?? null),
            $payload['query'],
        ]);

        return $payload;
    }

    private function extractSpecies(Pet $pet): ?string
    {
        return $this->cleanText($pet->pet_type ?? $pet->type ?? null);
    }

    private function extractAge(Pet $pet): ?string
    {
        $years = is_numeric($pet->pet_age ?? null) ? (int) $pet->pet_age : null;
        $months = is_numeric($pet->pet_age_months ?? null) ? (int) $pet->pet_age_months : null;

        $parts = [];
        if ($years !== null && $years > 0) {
            $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years');
        }
        if ($months !== null && $months > 0) {
            $parts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
        }

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        $dob = $pet->pet_dob ?? $pet->dob ?? null;
        if (! empty($dob)) {
            try {
                $dobDate = Carbon::parse((string) $dob);
                if (! $dobDate->isFuture()) {
                    $diffMonths = $dobDate->diffInMonths(now());
                    if ($diffMonths < 12) {
                        return $diffMonths . ' ' . ($diffMonths === 1 ? 'month' : 'months');
                    }

                    $diffYears = $dobDate->diffInYears(now());
                    return $diffYears . ' ' . ($diffYears === 1 ? 'year' : 'years');
                }
            } catch (\Throwable $e) {
                // Ignore invalid DOB and fall back to null.
            }
        }

        return null;
    }

    private function extractWeight(Pet $pet): ?string
    {
        $weight = $pet->weight ?? null;

        if ($weight === null || $weight === '') {
            return null;
        }

        if (is_numeric($weight)) {
            $number = rtrim(rtrim(number_format((float) $weight, 2, '.', ''), '0'), '.');
            return $number . ' kg';
        }

        return $this->cleanText($weight);
    }

    private function extractSex(Pet $pet): ?string
    {
        return $this->cleanText($pet->pet_gender ?? $pet->gender ?? null);
    }

    private function extractVaccinationSummary(Pet $pet): ?string
    {
        $vaccination = data_get($pet->dog_disease_payload, 'vaccination');

        if ($vaccination === null) {
            $lastVaccinationDate = $pet->vaccination_date?->toDateString() ?? $this->cleanText($pet->last_vaccenated_date ?? null);
            if ($lastVaccinationDate) {
                return 'Last vaccination recorded on ' . $lastVaccinationDate;
            }

            return null;
        }

        if (is_string($vaccination)) {
            $trimmed = trim($vaccination);
            if ($trimmed !== '') {
                return $trimmed;
            }

            return null;
        }

        if (! is_array($vaccination)) {
            return null;
        }

        if ($vaccination === []) {
            return 'No vaccination records available.';
        }

        if (array_is_list($vaccination)) {
            $items = [];
            foreach ($vaccination as $item) {
                if (is_scalar($item)) {
                    $value = $this->cleanText((string) $item);
                    if ($value) {
                        $items[] = $value;
                    }
                    continue;
                }

                if (is_array($item)) {
                    $name = $this->firstNonEmptyString([
                        $item['name'] ?? null,
                        $item['vaccine'] ?? null,
                        $item['title'] ?? null,
                    ]);
                    $date = $this->firstNonEmptyString([
                        $item['date'] ?? null,
                        $item['given_on'] ?? null,
                        $item['due_date'] ?? null,
                    ]);

                    if ($name && $date) {
                        $items[] = $name . ' (' . $date . ')';
                    } elseif ($name) {
                        $items[] = $name;
                    } elseif ($date) {
                        $items[] = $date;
                    }
                }
            }

            if ($items !== []) {
                return implode(', ', $items);
            }

            return json_encode($vaccination, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $parts = [];
        foreach ($vaccination as $key => $value) {
            if (is_scalar($value)) {
                $text = $this->cleanText((string) $value);
                if ($text) {
                    $parts[] = $key . ': ' . $text;
                }
            }
        }

        if ($parts !== []) {
            return implode('; ', $parts);
        }

        return json_encode($vaccination, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function extractMedicalHistory(Pet $pet): ?string
    {
        $medicalHistory = $pet->medical_history ?? null;

        if ($medicalHistory === null || $medicalHistory === '') {
            return null;
        }

        if (is_string($medicalHistory)) {
            $trimmed = trim($medicalHistory);
            if ($trimmed === '') {
                return null;
            }

            if ($trimmed === '[]' || $trimmed === '{}') {
                return 'No known issues';
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $summary = $this->summarizeMedicalArray($decoded);
                if ($summary !== null) {
                    return $summary;
                }
            }

            return $trimmed;
        }

        if (is_array($medicalHistory)) {
            return $this->summarizeMedicalArray($medicalHistory);
        }

        return null;
    }

    private function summarizeMedicalArray(array $items): ?string
    {
        if ($items === []) {
            return 'No known issues';
        }

        $parts = [];

        if (array_is_list($items)) {
            foreach ($items as $item) {
                if (is_scalar($item)) {
                    $value = $this->cleanText((string) $item);
                    if ($value) {
                        $parts[] = $value;
                    }
                    continue;
                }

                if (is_array($item)) {
                    $condition = $this->firstNonEmptyString([
                        $item['condition'] ?? null,
                        $item['name'] ?? null,
                        $item['issue'] ?? null,
                    ]);
                    $date = $this->firstNonEmptyString([
                        $item['date'] ?? null,
                        $item['reported_on'] ?? null,
                    ]);

                    if ($condition && $date) {
                        $parts[] = $condition . ' (' . $date . ')';
                    } elseif ($condition) {
                        $parts[] = $condition;
                    }
                }
            }

            if ($parts !== []) {
                return implode(', ', $parts);
            }

            return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        foreach ($items as $key => $value) {
            if (is_scalar($value)) {
                $text = $this->cleanText((string) $value);
                if ($text) {
                    $parts[] = $key . ': ' . $text;
                }
            }
        }

        if ($parts !== []) {
            return implode('; ', $parts);
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<int, mixed> $values
     */
    private function firstNonEmptyString(array $values): string
    {
        foreach ($values as $value) {
            $text = $this->cleanText($value);
            if ($text !== null) {
                return $text;
            }
        }

        return '';
    }

    private function cleanText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
