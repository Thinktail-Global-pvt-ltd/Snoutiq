<?php

namespace App\Services;

use App\Services\Ai\DogDiseaseSuggester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetDiseaseInferenceService
{
    public function syncFromReportedSymptom(
        int $petId,
        ?string $reportedSymptom,
        array $contextOverrides = [],
        ?string $source = null
    ): array {
        if (!Schema::hasTable('pets')) {
            return $this->resultPayload($reportedSymptom, null, null);
        }

        $pet = DB::table('pets')
            ->select([
                'id',
                'reported_symptom',
                'suggested_disease',
                'health_state',
                'name',
                'breed',
                'pet_age',
                'pet_gender',
            ])
            ->where('id', $petId)
            ->first();

        if (!$pet) {
            return $this->resultPayload($reportedSymptom, null, null);
        }

        $oldSymptom = $this->normalizeText($pet->reported_symptom ?? null);
        $newSymptom = $this->normalizeText($reportedSymptom);

        $suggestedDisease = null;
        $category = null;

        if ($newSymptom !== null) {
            $context = [
                'name' => $contextOverrides['name'] ?? ($pet->name ?? null),
                'breed' => $contextOverrides['breed'] ?? ($pet->breed ?? null),
                'pet_age' => $contextOverrides['pet_age'] ?? ($pet->pet_age ?? null),
                'pet_gender' => $contextOverrides['pet_gender'] ?? ($pet->pet_gender ?? null),
            ];

            $result = (new DogDiseaseSuggester())->suggest($newSymptom, $context);
            $suggestedDisease = $this->normalizeText($result['disease_name'] ?? null);
            $category = strtolower(trim((string) ($result['category'] ?? 'normal')));
            if (!in_array($category, ['normal', 'chronic'], true)) {
                $category = 'normal';
            }
        }

        $oldSuggestedDisease = $this->normalizeText($pet->suggested_disease ?? null);
        $updates = [];

        if (Schema::hasColumn('pets', 'reported_symptom') && $oldSymptom !== $newSymptom) {
            $updates['reported_symptom'] = $newSymptom;
        }
        if (Schema::hasColumn('pets', 'suggested_disease') && $oldSuggestedDisease !== $suggestedDisease) {
            $updates['suggested_disease'] = $suggestedDisease;
        }
        if (Schema::hasColumn('pets', 'health_state')) {
            $currentHealth = $this->normalizeText($pet->health_state ?? null);
            $nextHealth = $suggestedDisease === null ? null : $category;
            if ($currentHealth !== $nextHealth) {
                $updates['health_state'] = $nextHealth;
            }
        }

        if (!empty($updates)) {
            if (Schema::hasColumn('pets', 'updated_at')) {
                $updates['updated_at'] = now();
            }
            DB::table('pets')->where('id', $petId)->update($updates);
        }

        if ($oldSuggestedDisease !== $suggestedDisease) {
            $this->logSuggestedDiseaseChange(
                petId: $petId,
                lastReportedSymptom: $oldSymptom,
                currentReportedSymptom: $newSymptom,
                previousSuggestedDisease: $oldSuggestedDisease,
                newSuggestedDisease: $suggestedDisease,
                source: $source
            );
        }

        return $this->resultPayload($newSymptom, $suggestedDisease, $category);
    }

    private function logSuggestedDiseaseChange(
        int $petId,
        ?string $lastReportedSymptom,
        ?string $currentReportedSymptom,
        ?string $previousSuggestedDisease,
        ?string $newSuggestedDisease,
        ?string $source
    ): void {
        if (!Schema::hasTable('pet_suggested_disease_logs')) {
            return;
        }

        DB::table('pet_suggested_disease_logs')->insert([
            'pet_id' => $petId,
            'last_reported_symptom' => $lastReportedSymptom,
            'current_reported_symptom' => $currentReportedSymptom,
            'previous_suggested_disease' => $previousSuggestedDisease,
            'new_suggested_disease' => $newSuggestedDisease,
            'source' => $source,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function resultPayload(?string $reportedSymptom, ?string $suggestedDisease, ?string $category): array
    {
        return [
            'reported_symptom' => $this->normalizeText($reportedSymptom),
            'suggested_disease' => $this->normalizeText($suggestedDisease),
            'category' => $category,
        ];
    }
}
