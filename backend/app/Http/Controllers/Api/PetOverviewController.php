<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\GeminiConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PetOverviewController extends Controller
{
    public function show(Request $request, int $petId)
    {
        $pet = DB::table('pets')->where('id', $petId)->first();
        if (!$pet) {
            return response()->json(['success' => false, 'message' => 'Pet not found'], 404);
        }

        $dogDiseasePayload = $pet->dog_disease_payload ?? null;
        if (is_string($dogDiseasePayload)) {
            $dogDiseasePayload = json_decode($dogDiseasePayload, true);
        } elseif (is_object($dogDiseasePayload)) {
            $dogDiseasePayload = json_decode(json_encode($dogDiseasePayload), true);
        }
        if (!is_array($dogDiseasePayload)) {
            $dogDiseasePayload = null;
        }
        $dogDiseaseVaccination = $dogDiseasePayload['vaccination'] ?? null;
        $vaccinationAiSummary = $this->buildVaccinationAiSummary(
            is_array($dogDiseaseVaccination) ? $dogDiseaseVaccination : null,
            $pet->pet_card_for_ai ?? null
        );

        $owner = DB::table('users')->where('id', $pet->user_id)->first();

        $prescriptions = $this->fetchPrescriptions($petId);
        $vaccinations  = $this->fetchVaccinations($petId);
        $observation   = $this->fetchLatestObservation($owner?->id);

        $healthSignals = [
            'energy'   => $this->normalizeScore($observation['energy'] ?? null),
            'appetite' => $this->normalizeScore($observation['appetite'] ?? null),
            'mood'     => $this->normalizeScore($observation['mood'] ?? null),
        ];

        $clinicalRoadmap = [
            'condition'    => $pet->suggested_disease ?? null,
            'state'        => $pet->health_state ?? null,
            'next_consult' => $prescriptions['next_follow_up'] ?? null,
            'protocol'     => $prescriptions['protocol'] ?? null,
            'diagnosis'    => $prescriptions['latest_diagnosis'] ?? null,
        ];

        $careRoadmap = $vaccinations['care_roadmap'] ?? [];
        $medications = $this->buildMedications($prescriptions);
        $dailyCare = $this->fetchDailyCare($petId, $request->query('care_date'));
        $latestInClinicAppointment = $this->fetchLatestInClinicAppointment($petId);
        $latestVideoCallingAppointment = $this->fetchLatestVideoCallingAppointment($petId);
        $isNueteredRaw = property_exists($pet, 'is_nuetered')
            ? $pet->is_nuetered
            : (property_exists($pet, 'is_neutered') ? $pet->is_neutered : null);
        $isNuetered = $this->normalizeYesNoFlag($isNueteredRaw);
        $dewormingYesNo = property_exists($pet, 'deworming_yes_no')
            ? $this->normalizeYesNoFlag($pet->deworming_yes_no)
            : null;
        $lastDewormingDate = property_exists($pet, 'last_deworming_date') ? $pet->last_deworming_date : null;
        $dewormingStatus = property_exists($pet, 'deworming_status') ? $pet->deworming_status : null;
        $nextDewormingDate = property_exists($pet, 'next_deworming_date') ? $pet->next_deworming_date : null;

        return response()->json([
            'success' => true,
            'data' => [
                'pet' => [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'breed' => $pet->breed,
                    'age_years' => $pet->pet_age,
                    'age_months' => $pet->pet_age_months,
                    'gender' => $pet->pet_gender,
                    'state' => $pet->health_state,
                    'ai_summary' => $pet->ai_summary,
                    'reported_symptom' => $pet->reported_symptom,
                    'suggested_disease' => $pet->suggested_disease,
                    'image' => $pet->pet_doc1 ?? $pet->pet_doc2 ?? null,
                    'is_nuetered' => $isNuetered,
                    'is_neutered' => $isNuetered,
                    'deworming_yes_no' => $dewormingYesNo,
                    'last_deworming_date' => $lastDewormingDate,
                    'deworming_status' => $dewormingStatus,
                    'next_deworming_date' => $nextDewormingDate,
                ],
                'owner' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'phone' => $owner->phone,
                    'email' => $owner->email,
                ] : null,
                'clinical_roadmap' => $clinicalRoadmap,
                'health_signals' => $healthSignals,
                'health_signals_icons' => [
                    'eating' => $healthSignals['appetite'],
                    'activity' => null,
                    'digestion' => null,
                    'behavior' => $healthSignals['mood'],
                ],
                'latest_observation' => $observation,
                'prescriptions' => $prescriptions['items'],
                'medications' => $medications,
                'care_roadmap' => $careRoadmap,
                'vaccination' => $dogDiseaseVaccination,
                'vaccination_ai_summary' => $vaccinationAiSummary,
                'observation_note' => $observation['notes'] ?? null,
                'knowledge_hub' => $this->knowledgeHubSuggestions($pet),
                'today_care' => $dailyCare,
                'in_clinic_appointment' => $latestInClinicAppointment,
                'video_call_appointment' => $latestVideoCallingAppointment,
                'latest_appointments' => [
                    'in_clinic' => $latestInClinicAppointment,
                    'video_call' => $latestVideoCallingAppointment,
                ],
                'deworming' => [
                    'deworming_yes_no' => $dewormingYesNo,
                    'last_deworming_date' => $lastDewormingDate,
                    'deworming_status' => $dewormingStatus,
                    'next_deworming_date' => $nextDewormingDate,
                ],
            ],
        ]);
    }

    /**
     * GET|POST /api/pets/check/vaccination-deworming-null
     * Input: pet_id (query or body)
     * Output: vaccination_is_null, deworming_is_null
     */
    public function vaccinationDewormingNullStatus(Request $request)
    {
        $rawPetId = $request->input('pet_id', $request->query('pet_id'));
        $petId = is_numeric($rawPetId) ? (int) $rawPetId : 0;

        if ($petId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pet_id is required',
            ], 422);
        }

        if (!Schema::hasTable('pets')) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is missing',
            ], 500);
        }

        $columns = ['id'];
        if (Schema::hasColumn('pets', 'last_deworming_date')) {
            $columns[] = 'last_deworming_date';
        }
        if (Schema::hasColumn('pets', 'dog_disease_payload')) {
            $columns[] = 'dog_disease_payload';
        }

        $pet = DB::table('pets')->select($columns)->where('id', $petId)->first();
        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $dogDiseasePayload = property_exists($pet, 'dog_disease_payload')
            ? $pet->dog_disease_payload
            : null;
        if (is_string($dogDiseasePayload)) {
            $dogDiseasePayload = json_decode($dogDiseasePayload, true);
        } elseif (is_object($dogDiseasePayload)) {
            $dogDiseasePayload = json_decode(json_encode($dogDiseasePayload), true);
        }
        if (!is_array($dogDiseasePayload)) {
            $dogDiseasePayload = null;
        }

        $vaccinationValue = is_array($dogDiseasePayload) && array_key_exists('vaccination', $dogDiseasePayload)
            ? $dogDiseasePayload['vaccination']
            : null;
        $lastDewormingDate = property_exists($pet, 'last_deworming_date')
            ? $pet->last_deworming_date
            : null;

        return response()->json([
            'success' => true,
            'pet_id' => $petId,
            'vaccination_is_null' => $vaccinationValue === null,
            'deworming_is_null' => $lastDewormingDate === null || trim((string) $lastDewormingDate) === '',
        ]);
    }

    /**
     * POST /api/pets/deworming-vaccination
     * Input: pet_id, deworming_yes_no, last_deworming_date, vaccination_json (or vaccinations_json / vaccination)
     * Saves: deworming_yes_no, last_deworming_date, next_deworming_date, dog_disease_payload.vaccination
     */
    public function updateDewormingVaccination(Request $request)
    {
        $rawPetId = $request->input('pet_id', $request->query('pet_id'));
        $petId = is_numeric($rawPetId) ? (int) $rawPetId : 0;

        if ($petId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pet_id is required',
            ], 422);
        }

        $hasLastDewormingInput = $request->exists('last_deworming_date');
        $normalizedLastDewormingDate = null;
        if ($hasLastDewormingInput) {
            $rawLastDewormingDate = $request->input('last_deworming_date');
            if ($rawLastDewormingDate === null || trim((string) $rawLastDewormingDate) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'last_deworming_date must be a valid date',
                ], 422);
            }
            $normalizedLastDewormingDate = $this->normalizeDateOnly($rawLastDewormingDate);
            if ($normalizedLastDewormingDate === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'last_deworming_date must be a valid date',
                ], 422);
            }
        }

        $hasDewormingYesNoInput = $request->exists('deworming_yes_no') || $request->exists('deworming');
        $normalizedDewormingYesNo = null;
        if ($hasDewormingYesNoInput) {
            $rawDewormingYesNo = $request->input('deworming_yes_no', $request->input('deworming'));
            if ($rawDewormingYesNo === null || trim((string) $rawDewormingYesNo) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'deworming_yes_no must be boolean-like (true/false/1/0/Y/N)',
                ], 422);
            }

            $normalizedDewormingYesNo = $this->normalizeYesNoFlag($rawDewormingYesNo);
            if ($normalizedDewormingYesNo === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'deworming_yes_no must be boolean-like (true/false/1/0/Y/N)',
                ], 422);
            }
        }

        [$hasVaccinationInput, $vaccinationPayload, $vaccinationError] = $this->extractVaccinationPayloadFromRequest($request);
        if ($vaccinationError !== null) {
            return response()->json([
                'success' => false,
                'message' => $vaccinationError,
            ], 422);
        }
        if (! $hasVaccinationInput && ! $hasLastDewormingInput && ! $hasDewormingYesNoInput) {
            return response()->json([
                'success' => false,
                'message' => 'Provide at least one field: deworming_yes_no, last_deworming_date or vaccination_json',
            ], 422);
        }

        if (! Schema::hasTable('pets')) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is missing',
            ], 500);
        }

        if ($hasVaccinationInput && ! Schema::hasColumn('pets', 'dog_disease_payload')) {
            return response()->json([
                'success' => false,
                'message' => 'pets.dog_disease_payload column is missing',
            ], 500);
        }
        if ($hasLastDewormingInput && ! Schema::hasColumn('pets', 'last_deworming_date')) {
            return response()->json([
                'success' => false,
                'message' => 'pets.last_deworming_date column is missing',
            ], 500);
        }
        if ($hasDewormingYesNoInput && ! Schema::hasColumn('pets', 'deworming_yes_no')) {
            return response()->json([
                'success' => false,
                'message' => 'pets.deworming_yes_no column is missing',
            ], 500);
        }

        $petSelectColumns = ['id'];
        if (Schema::hasColumn('pets', 'dog_disease_payload')) {
            $petSelectColumns[] = 'dog_disease_payload';
        }
        if (Schema::hasColumn('pets', 'pet_dob')) {
            $petSelectColumns[] = 'pet_dob';
        }
        if (Schema::hasColumn('pets', 'dob')) {
            $petSelectColumns[] = 'dob';
        }

        $pet = DB::table('pets')
            ->select($petSelectColumns)
            ->where('id', $petId)
            ->first();

        if (! $pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $updates = [];
        $dewormingSchedule = [
            'deworming_status' => null,
            'next_deworming_date' => null,
        ];

        if ($hasVaccinationInput) {
            $dogDiseasePayload = $this->decodeDogDiseasePayload($pet->dog_disease_payload ?? null) ?? [];
            $dogDiseasePayload['vaccination'] = $vaccinationPayload;
            $updates['dog_disease_payload'] = json_encode($dogDiseasePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($hasLastDewormingInput) {
            $updates['last_deworming_date'] = $normalizedLastDewormingDate;

            $petDob = property_exists($pet, 'pet_dob')
                ? $pet->pet_dob
                : (property_exists($pet, 'dob') ? $pet->dob : null);
            $dewormingSchedule = $this->resolveDewormingScheduleFromDob(
                is_string($petDob) ? $petDob : null,
                $normalizedLastDewormingDate
            );

            if (Schema::hasColumn('pets', 'next_deworming_date') && !empty($dewormingSchedule['next_deworming_date'])) {
                $updates['next_deworming_date'] = $dewormingSchedule['next_deworming_date'];
            }
            if (Schema::hasColumn('pets', 'deworming_status') && !empty($dewormingSchedule['deworming_status'])) {
                $updates['deworming_status'] = $dewormingSchedule['deworming_status'];
            }
        }
        if ($hasDewormingYesNoInput && Schema::hasColumn('pets', 'deworming_yes_no')) {
            $updates['deworming_yes_no'] = $normalizedDewormingYesNo;
        }

        if (Schema::hasColumn('pets', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('pets')->where('id', $petId)->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => $petId,
                'updated_fields' => array_values(array_diff(array_keys($updates), ['updated_at'])),
                'deworming_yes_no' => $hasDewormingYesNoInput ? $normalizedDewormingYesNo : null,
                'last_deworming_date' => $hasLastDewormingInput ? $normalizedLastDewormingDate : null,
                'next_deworming_date' => $hasLastDewormingInput ? ($dewormingSchedule['next_deworming_date'] ?? null) : null,
                'deworming_status' => $hasLastDewormingInput ? ($dewormingSchedule['deworming_status'] ?? null) : null,
                'vaccination' => $hasVaccinationInput ? $vaccinationPayload : null,
            ],
        ]);
    }

    /**
     * POST /api/pets/neutered-status
     * Input: pet_id, is_nuitered (or is_nuetered / is_neutered)
     * Updates neutered status in available column(s): is_nuetered and/or is_neutered.
     */
    public function updateNeuteredStatus(Request $request)
    {
        $rawPetId = $request->input('pet_id', $request->query('pet_id'));
        $petId = is_numeric($rawPetId) ? (int) $rawPetId : 0;
        if ($petId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pet_id is required',
            ], 422);
        }

        $hasNeuteredInput = $request->exists('is_nuitered')
            || $request->exists('is_nuetered')
            || $request->exists('is_neutered');
        if (! $hasNeuteredInput) {
            return response()->json([
                'success' => false,
                'message' => 'is_nuitered is required',
            ], 422);
        }

        $rawNeutered = $request->input(
            'is_nuitered',
            $request->input('is_nuetered', $request->input('is_neutered'))
        );
        $normalizedNeutered = $this->normalizeYesNoFlag($rawNeutered);
        if ($normalizedNeutered === null) {
            return response()->json([
                'success' => false,
                'message' => 'is_nuitered must be boolean-like (true/false/1/0/Y/N)',
            ], 422);
        }

        if (! Schema::hasTable('pets')) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is missing',
            ], 500);
        }

        $hasIsNueteredColumn = Schema::hasColumn('pets', 'is_nuetered');
        $hasIsNeuteredColumn = Schema::hasColumn('pets', 'is_neutered');
        if (! $hasIsNueteredColumn && ! $hasIsNeuteredColumn) {
            return response()->json([
                'success' => false,
                'message' => 'Neither is_nuetered nor is_neutered column exists on pets table',
            ], 500);
        }

        $selectColumns = ['id'];
        if ($hasIsNueteredColumn) {
            $selectColumns[] = 'is_nuetered';
        }
        if ($hasIsNeuteredColumn) {
            $selectColumns[] = 'is_neutered';
        }

        $pet = DB::table('pets')
            ->select($selectColumns)
            ->where('id', $petId)
            ->first();
        if (! $pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $updates = [];
        if ($hasIsNueteredColumn) {
            $updates['is_nuetered'] = $this->neuteredValueForColumn('is_nuetered', $normalizedNeutered);
        }
        if ($hasIsNeuteredColumn) {
            $updates['is_neutered'] = $this->neuteredValueForColumn('is_neutered', $normalizedNeutered);
        }
        if (Schema::hasColumn('pets', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('pets')->where('id', $petId)->update($updates);

        $updatedPet = DB::table('pets')
            ->select($selectColumns)
            ->where('id', $petId)
            ->first();

        $isNueteredRaw = property_exists($updatedPet, 'is_nuetered')
            ? $updatedPet->is_nuetered
            : null;
        $isNeuteredRaw = property_exists($updatedPet, 'is_neutered')
            ? $updatedPet->is_neutered
            : null;
        $normalizedCurrentValue = $this->normalizeYesNoFlag(
            $isNueteredRaw !== null ? $isNueteredRaw : $isNeuteredRaw
        );

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => $petId,
                'is_nuitered' => $normalizedCurrentValue,
                'is_nuetered' => $normalizedCurrentValue,
                'is_neutered' => $normalizedCurrentValue,
                'stored_values' => [
                    'is_nuetered' => $isNueteredRaw,
                    'is_neutered' => $isNeuteredRaw,
                ],
                'updated_fields' => array_values(array_diff(array_keys($updates), ['updated_at'])),
            ],
        ]);
    }

    private function normalizeDateOnly($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function decodeDogDiseasePayload($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function extractVaccinationPayloadFromRequest(Request $request): array
    {
        $candidateKeys = ['vaccination_json', 'vaccinations_json', 'vaccination'];
        $inputKey = null;
        $rawValue = null;

        foreach ($candidateKeys as $key) {
            if ($request->exists($key)) {
                $inputKey = $key;
                $rawValue = $request->input($key);
                break;
            }
        }

        if ($inputKey === null) {
            return [false, null, null];
        }

        if (is_string($rawValue)) {
            $trimmed = trim($rawValue);
            if ($trimmed === '') {
                return [true, null, null];
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [true, null, "{$inputKey} must be valid JSON"];
            }

            return [true, $decoded, null];
        }

        if (is_object($rawValue)) {
            return [true, json_decode(json_encode($rawValue), true), null];
        }

        if (is_array($rawValue) || is_null($rawValue) || is_bool($rawValue) || is_numeric($rawValue)) {
            return [true, $rawValue, null];
        }

        return [true, null, "{$inputKey} format is not supported"];
    }

    private function resolveDewormingScheduleFromDob(?string $petDob, ?string $lastDewormingDate): array
    {
        $normalizedDob = $this->normalizeDateOnly($petDob);
        if ($normalizedDob === null) {
            return [
                'deworming_status' => null,
                'next_deworming_date' => null,
            ];
        }

        try {
            $dob = Carbon::parse($normalizedDob)->startOfDay();
        } catch (\Throwable $e) {
            return [
                'deworming_status' => null,
                'next_deworming_date' => null,
            ];
        }

        $today = Carbon::today();
        $ageInMonths = max(0, $dob->diffInMonths($today, false));
        $isUnderSixMonths = $ageInMonths < 6;
        $status = $isUnderSixMonths ? 'every_15_days' : 'every_3_months';

        $normalizedLastDewormingDate = $this->normalizeDateOnly($lastDewormingDate);
        $baseDate = $normalizedLastDewormingDate
            ? Carbon::parse($normalizedLastDewormingDate)->startOfDay()
            : $dob->copy();

        $nextDate = $baseDate->copy();
        if ($isUnderSixMonths) {
            do {
                $nextDate->addDays(15);
            } while ($nextDate->lte($today));
        } else {
            do {
                $nextDate->addMonthsNoOverflow(3);
            } while ($nextDate->lte($today));
        }

        return [
            'deworming_status' => $status,
            'next_deworming_date' => $nextDate->toDateString(),
        ];
    }

    private function normalizeYesNoFlag($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $normalized = strtolower(trim((string) $value, " \t\n\r\0\x0B\"'"));
        if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
            return 1;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return 0;
        }

        return null;
    }

    private function neuteredValueForColumn(string $column, int $value)
    {
        if ($this->petColumnUsesEnumStyle($column)) {
            return $value === 1 ? 'Y' : 'N';
        }

        return $value === 1 ? 1 : 0;
    }

    private function petColumnUsesEnumStyle(string $column): bool
    {
        if (! Schema::hasTable('pets') || ! Schema::hasColumn('pets', $column)) {
            return false;
        }

        $columnType = null;
        try {
            $columnType = Schema::getColumnType('pets', $column);
        } catch (\Throwable $e) {
            $columnType = null;
        }

        $columnTypeNormalized = strtolower(trim((string) $columnType));
        if (str_contains($columnTypeNormalized, 'enum')
            || in_array($columnTypeNormalized, ['string', 'char', 'varchar'], true)) {
            return true;
        }

        if ($columnTypeNormalized === '' || $columnTypeNormalized === 'unknown') {
            try {
                $columnMeta = DB::selectOne("SHOW COLUMNS FROM `pets` LIKE ?", [$column]);
                $rawType = strtolower((string) ($columnMeta->Type ?? $columnMeta->type ?? ''));
                if (str_contains($rawType, 'enum(')) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Use non-enum fallback when metadata lookup fails.
            }
        }

        return false;
    }

    private function fetchDailyCare(int $petId, ?string $careDate = null): ?array
    {
        if (!Schema::hasTable('pet_daily_cares')) {
            return null;
        }

        $todayDate = Carbon::today()->toDateString();
        $date = $todayDate;
        $hasRequestedDate = $careDate !== null && trim($careDate) !== '';
        if ($hasRequestedDate) {
            try {
                $date = Carbon::parse($careDate)->toDateString();
            } catch (\Throwable $e) {
                // Keep today's date when incoming care_date is invalid.
            }
        }

        $rows = $this->loadDailyCareRowsForDate($petId, $date);

        // If no specific date was requested, fallback to latest saved daily care.
        if (! $hasRequestedDate && $rows->isEmpty()) {
            $latestDate = DB::table('pet_daily_cares')
                ->where('pet_id', $petId)
                ->max('care_date');

            if (is_string($latestDate) && trim($latestDate) !== '') {
                $date = Carbon::parse($latestDate)->toDateString();
                $rows = $this->loadDailyCareRowsForDate($petId, $date);
            }
        }

        $bundleRow = $rows->first(function ($row) {
            return ($row->task_key ?? null) === '__daily_bundle__';
        });
        if ($bundleRow) {
            $bundleData = $this->decodeDailyCareBundleData($bundleRow->notes ?? null);
            if ($bundleData !== null) {
                return $this->buildDailyCarePayload(
                    $date,
                    $bundleData['items'] ?? [],
                    $bundleData['notes'] ?? null
                );
            }
        }

        $legacyItems = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'task_key' => $row->task_key ?? null,
                'title' => $row->title,
                'scheduled_time' => $row->scheduled_time,
                'icon' => $row->icon,
                'is_completed' => (bool) ($row->is_completed ?? false),
                'completed_at' => $row->completed_at,
                'sort_order' => (int) ($row->sort_order ?? 0),
                'notes' => null,
            ];
        })->values()->all();

        foreach ($legacyItems as $index => &$item) {
            $item['id'] = $index + 1;
        }
        unset($item);

        return $this->buildDailyCarePayload($date, $legacyItems, null);
    }

    private function loadDailyCareRowsForDate(int $petId, string $date)
    {
        return DB::table('pet_daily_cares')
            ->where('pet_id', $petId)
            ->whereDate('care_date', $date)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function decodeDailyCareBundleData($notes): ?array
    {
        if (!is_string($notes) || trim($notes) === '') {
            return null;
        }

        $decoded = json_decode($notes, true);
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
            return null;
        }

        $rawDailyNotes = $decoded['notes'] ?? ($decoded['daily_notes'] ?? null);
        $dailyNotes = is_string($rawDailyNotes) && trim($rawDailyNotes) !== ''
            ? trim($rawDailyNotes)
            : null;

        $normalized = [];
        foreach ($decoded['items'] as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'id' => isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null,
                'task_key' => isset($item['task_key']) && $item['task_key'] !== ''
                    ? trim((string) $item['task_key'])
                    : null,
                'title' => trim((string) ($item['title'] ?? '')),
                'scheduled_time' => isset($item['scheduled_time']) && $item['scheduled_time'] !== ''
                    ? trim((string) $item['scheduled_time'])
                    : null,
                'icon' => isset($item['icon']) && $item['icon'] !== '' ? trim((string) $item['icon']) : null,
                'is_completed' => (bool) ($item['is_completed'] ?? false),
                'completed_at' => isset($item['completed_at']) && $item['completed_at'] !== ''
                    ? (string) $item['completed_at']
                    : null,
                'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : (int) $index,
                'notes' => null,
                '_index' => $index,
            ];
        }

        usort($normalized, function (array $a, array $b): int {
            $sortCompare = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            if ($sortCompare !== 0) {
                return $sortCompare;
            }

            return ($a['_index'] ?? 0) <=> ($b['_index'] ?? 0);
        });

        foreach ($normalized as &$item) {
            unset($item['_index']);
        }
        unset($item);

        foreach ($normalized as $index => &$item) {
            $item['id'] = $index + 1;
        }
        unset($item);

        return [
            'notes' => $dailyNotes,
            'items' => $normalized,
        ];
    }

    private function buildDailyCarePayload(string $careDate, array $items, ?string $dailyNotes = null): array
    {
        $doneCount = count(array_filter($items, function (array $item): bool {
            return (bool) ($item['is_completed'] ?? false);
        }));
        $totalCount = count($items);

        return [
            'care_date' => $careDate,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'progress_text' => "{$doneCount}/{$totalCount} done",
            'notes' => $dailyNotes,
            'items' => array_values($items),
        ];
    }

    private function fetchLatestInClinicAppointment(int $petId): ?array
    {
        if (!Schema::hasTable('appointments') || !Schema::hasColumn('appointments', 'pet_id')) {
            return null;
        }

        $query = DB::table('appointments as a')->where('a.pet_id', $petId);

        $select = [
            'a.id',
            'a.pet_id',
            'a.vet_registeration_id',
            'a.doctor_id',
            'a.name as patient_name',
            'a.mobile as patient_mobile',
            'a.pet_name',
            'a.appointment_date',
            'a.appointment_time',
            'a.status',
            'a.notes',
            'a.created_at',
            'a.updated_at',
        ];

        if (Schema::hasTable('doctors')) {
            $query->leftJoin('doctors as d', 'd.id', '=', 'a.doctor_id');
            if (Schema::hasColumn('doctors', 'doctor_name')) {
                $select[] = 'd.doctor_name as doctor_name';
            } elseif (Schema::hasColumn('doctors', 'name')) {
                $select[] = 'd.name as doctor_name';
            } elseif (Schema::hasColumn('doctors', 'full_name')) {
                $select[] = 'd.full_name as doctor_name';
            }
        }

        if (Schema::hasTable('vet_registerations_temp')) {
            $query->leftJoin('vet_registerations_temp as v', 'v.id', '=', 'a.vet_registeration_id');
            $select[] = 'v.name as clinic_name';
        }

        $row = $query
            ->select($select)
            ->orderByDesc('a.appointment_date')
            ->orderByDesc('a.appointment_time')
            ->orderByDesc('a.id')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'id' => $row->id,
            'pet_id' => $row->pet_id,
            'vet_registeration_id' => $row->vet_registeration_id,
            'doctor_id' => $row->doctor_id,
            'doctor_name' => $row->doctor_name ?? null,
            'clinic_name' => $row->clinic_name ?? null,
            'patient_name' => $row->patient_name,
            'patient_mobile' => $row->patient_mobile,
            'pet_name' => $row->pet_name,
            'appointment_date' => $row->appointment_date,
            'appointment_time' => $row->appointment_time,
            'status' => $row->status,
            'notes' => $this->decodeJsonMaybe($row->notes),
            'created_at' => $this->toIso8601String($row->created_at ?? null),
            'updated_at' => $this->toIso8601String($row->updated_at ?? null),
            'source' => 'appointments',
            'appointment_mode' => 'in_clinic',
        ];
    }

    private function fetchLatestVideoCallingAppointment(int $petId): ?array
    {
        if (!Schema::hasTable('transactions') || !Schema::hasColumn('transactions', 'pet_id')) {
            return null;
        }

        $query = DB::table('transactions as t')
            ->where('t.pet_id', $petId)
            ->whereIn('t.type', ['excell_export_campaign', 'video_consult']);

        $select = [
            't.id',
            't.pet_id',
            't.user_id',
            't.doctor_id',
            't.clinic_id',
            't.amount_paise',
            't.status',
            't.type',
            't.payment_method',
            't.reference',
            't.metadata',
            't.created_at',
            't.updated_at',
        ];

        if (Schema::hasColumn('transactions', 'channel_name')) {
            $select[] = 't.channel_name';
        }

        if (Schema::hasTable('users')) {
            $query->leftJoin('users as u', 'u.id', '=', 't.user_id');
            $select[] = 'u.name as user_name';
        }

        if (Schema::hasTable('doctors')) {
            $query->leftJoin('doctors as d', 'd.id', '=', 't.doctor_id');
            if (Schema::hasColumn('doctors', 'doctor_name')) {
                $select[] = 'd.doctor_name as doctor_name';
            } elseif (Schema::hasColumn('doctors', 'name')) {
                $select[] = 'd.name as doctor_name';
            } elseif (Schema::hasColumn('doctors', 'full_name')) {
                $select[] = 'd.full_name as doctor_name';
            }
        }

        if (Schema::hasTable('vet_registerations_temp')) {
            $query->leftJoin('vet_registerations_temp as v', 'v.id', '=', 't.clinic_id');
            $select[] = 'v.name as clinic_name';
        }

        $row = $query
            ->select($select)
            ->orderByDesc('t.created_at')
            ->orderByDesc('t.id')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'id' => $row->id,
            'pet_id' => $row->pet_id,
            'user_id' => $row->user_id,
            'user_name' => $row->user_name ?? null,
            'doctor_id' => $row->doctor_id,
            'doctor_name' => $row->doctor_name ?? null,
            'clinic_id' => $row->clinic_id,
            'clinic_name' => $row->clinic_name ?? null,
            'amount_paise' => $row->amount_paise !== null ? (int) $row->amount_paise : null,
            'status' => $row->status,
            'type' => $row->type,
            'payment_method' => $row->payment_method,
            'reference' => $row->reference,
            'channel_name' => $row->channel_name ?? null,
            'metadata' => $this->decodeJsonMaybe($row->metadata),
            'created_at' => $this->toIso8601String($row->created_at ?? null),
            'updated_at' => $this->toIso8601String($row->updated_at ?? null),
            'source' => 'transactions',
            'appointment_mode' => 'video_call',
        ];
    }

    private function decodeJsonMaybe($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    private function toIso8601String($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        try {
            return Carbon::parse((string) $value)->toIso8601String();
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    }

    private function fetchPrescriptions(int $petId): array
    {
        if (!Schema::hasTable('prescriptions')) {
            return ['items' => [], 'medications' => [], 'next_follow_up' => null, 'protocol' => null];
        }

        $items = DB::table('prescriptions')
            ->select('prescriptions.*')
            ->where(function ($q) use ($petId) {
                if (Schema::hasColumn('prescriptions', 'pet_id')) {
                    $q->where('pet_id', $petId);
                }
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $nextFollowUp = $items->first()?->follow_up_date;
        $protocol     = $items->first()?->treatment_plan;
        $latestDiagnosis = $items->first()?->diagnosis ?? $items->first()?->disease_name;

        $medications = [];
        foreach ($items as $p) {
            $medJson = $p->medications_json ? json_decode($p->medications_json, true) : null;
            if (is_array($medJson) && $medJson) {
                foreach ($medJson as $med) {
                    $medications[] = [
                        'title' => $med['name'] ?? 'Medication',
                        'details' => Str::of(($med['dose'] ?? ''))->append(' ', ($med['frequency'] ?? ''), ' ', ($med['duration'] ?? ''))->trim()->value(),
                        'route' => $med['route'] ?? '',
                        'notes' => $med['notes'] ?? '',
                        'prescription_id' => $p->id,
                    ];
                }
            }

            if (!empty($p->treatment_plan)) {
                $medications[] = [
                    'title' => 'Treatment plan',
                    'details' => $p->treatment_plan,
                    'prescription_id' => $p->id,
                ];
            }
            if (!empty($p->home_care)) {
                $medications[] = [
                    'title' => 'Home care',
                    'details' => $p->home_care,
                    'prescription_id' => $p->id,
                ];
            }
        }

        return [
            'items' => $items,
            'medications' => $medications,
            'next_follow_up' => $nextFollowUp,
            'protocol' => $protocol,
            'latest_diagnosis' => $latestDiagnosis,
        ];
    }

    private function fetchVaccinations(int $petId): array
    {
        if (!Schema::hasTable('pet_vaccination_records')) {
            return ['care_roadmap' => []];
        }

        $records = DB::table('pet_vaccination_records')
            ->select(['id', 'recommendations', 'notes', 'as_of_date', 'life_stage', 'age_display'])
            ->where('pet_id', $petId)
            ->orderByDesc('as_of_date')
            ->limit(3)
            ->get();

        $care = [];
        foreach ($records as $rec) {
            $recs = json_decode($rec->recommendations ?? '[]', true);
            if (is_array($recs)) {
                foreach ($recs as $r) {
                    $care[] = [
                        'title' => $r['title'] ?? 'Care item',
                        'status' => $r['status'] ?? null,
                        'due' => $r['due'] ?? null,
                        'note' => $r['note'] ?? ($rec->notes ?? null),
                    ];
                }
            }
        }

        return ['care_roadmap' => $care];
    }

    private function fetchLatestObservation(?int $userId): ?array
    {
        if (!$userId || !Schema::hasTable('user_observations')) {
            return null;
        }

        $obs = DB::table('user_observations')
            ->where('user_id', $userId)
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->first();

        if (!$obs) {
            return null;
        }

        return $this->serializeObservationRow($obs);
    }

    private function serializeObservationRow(object $obs): array
    {
        $data = (array) $obs;

        if (array_key_exists('symptoms', $data) && is_string($data['symptoms'])) {
            $decodedSymptoms = json_decode($data['symptoms'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['symptoms'] = $decodedSymptoms;
            }
        }

        $hasBlobColumn = Schema::hasColumn('user_observations', 'image_blob');
        $hasMimeColumn = Schema::hasColumn('user_observations', 'image_mime');
        $hasImageBlob = $hasBlobColumn && !empty($obs->image_blob);

        $data['image_blob_url'] = ($hasBlobColumn && $hasMimeColumn && $hasImageBlob)
            ? route('api.user-per-observationss.image', ['observation' => $obs->id])
            : null;
        $data['image_url'] = $data['image_blob_url'];

        if ($hasBlobColumn && array_key_exists('image_blob', $data)) {
            unset($data['image_blob']);
            $data['image_blob_present'] = $hasImageBlob;
        }

        return $data;
    }

    private function normalizeScore($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            $v = (int)$value;
            return max(0, min(100, $v));
        }

        $map = [
            'low' => 30,
            'med' => 60,
            'medium' => 60,
            'ok' => 70,
            'good' => 80,
            'high' => 90,
            'great' => 95,
        ];
        $key = strtolower(trim((string)$value));
        return $map[$key] ?? null;
    }

    private function knowledgeHubSuggestions(object $pet): array
    {
        $breed = $pet->breed ?: 'dog';
        return [
            [
                'title' => 'Understanding '.$breed.' joint health',
                'tag' => 'Preventative',
                'duration' => '4 min read',
            ],
            [
                'title' => 'Seasonal care tips for active pets',
                'tag' => 'Seasonal',
                'duration' => '6 min read',
            ],
        ];
    }

    private function buildMedications(array $prescriptions): array
    {
        if (!empty($prescriptions['medications'])) {
            return $prescriptions['medications'];
        }
        $items = $prescriptions['items'] ?? [];
        $meds = [];
        foreach ($items as $p) {
            if (!empty($p->treatment_plan)) {
                $meds[] = [
                    'title' => 'Treatment plan',
                    'details' => $p->treatment_plan,
                    'prescription_id' => $p->id,
                ];
            }
        }
        return $meds;
    }

    private function buildVaccinationAiSummary(?array $vaccinationPayload, ?string $petCardPath): ?array
    {
        if (empty($vaccinationPayload) && empty($petCardPath)) {
            return null;
        }

        $prompt = $this->buildVaccinationPrompt($vaccinationPayload);
        $imagePath = $this->resolvePetCardPath($petCardPath);
        if (empty($vaccinationPayload) && !$imagePath) {
            return null;
        }

        $raw = $this->callGeminiApi_curl($prompt, $imagePath);
        if (str_starts_with($raw, 'AI error:')) {
            return null;
        }

        $decoded = $this->decodeGeminiJson($raw);
        return $decoded ?: null;
    }

    private function buildVaccinationPrompt(?array $vaccinationPayload): string
    {
        $prompt = <<<PROMPT
You are reading a veterinary vaccination record.

From the uploaded document, extract ONLY vaccination-related information.

Return structured JSON with the following fields:
- pet_name (if visible)
- vaccine_name
- vaccine_type (core / rabies / lepto / booster / unknown)
- date_given (ISO format if possible, else raw text)
- next_due_date (ISO format if possible, else raw text)
- vet_name (if visible)
- confidence (high / medium / low)

Rules:
- Ignore non-vaccination text.
- Do NOT guess missing dates.
- If handwriting is unclear, mark confidence as low.
- Multiple vaccines should be returned as an array.
- Do NOT diagnose or add medical advice.

Output JSON only.
PROMPT;

        if (!empty($vaccinationPayload)) {
            $payloadJson = json_encode($vaccinationPayload, JSON_UNESCAPED_SLASHES);
            $prompt .= "\n\nVaccination data (from form):\n".$payloadJson;
        }

        return $prompt;
    }

    private function resolvePetCardPath(?string $petCardPath): ?string
    {
        if (!$petCardPath || !is_string($petCardPath)) {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $petCardPath)) {
            return null;
        }

        $trimmed = ltrim($petCardPath, '/');
        if (str_starts_with($trimmed, 'backend/')) {
            $trimmed = substr($trimmed, strlen('backend/'));
        }

        $candidate = public_path($trimmed);
        if (is_readable($candidate)) {
            return $candidate;
        }

        return null;
    }

    private function callGeminiApi_curl(string $prompt, ?string $imagePath, int $attempt = 1): string
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY')));
        if (empty($apiKey)) {
            return "AI error: Gemini API key is not configured.";
        }
        $model  = GeminiConfig::chatModel();

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        $parts = [['text' => $prompt]];
        if ($imagePath && is_readable($imagePath)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($imagePath) : 'application/octet-stream';
            $data = base64_encode(file_get_contents($imagePath));
            $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $data]];
        }

        $payload = json_encode([
            'contents' => [[
                'role'  => 'user',
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'maxOutputTokens' => 450,
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err  = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            Log::error('Gemini cURL error', ['err' => $err, 'info' => $info]);
            return "AI error: ".$err;
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            Log::error('Gemini HTTP non-2xx', ['status' => $http, 'body' => $resp]);
            $message = $this->extractGeminiErrorMessage($resp, $http);
            $shouldRetry = $attempt < 3 && ($http === 429 || str_contains(strtolower($message), 'resource exhausted'));
            if ($shouldRetry) {
                usleep(200000 * $attempt);
                return $this->callGeminiApi_curl($prompt, $imagePath, $attempt + 1);
            }
            return "AI error: {$message}";
        }

        $json = json_decode($resp, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? "No response.";
    }

    private function extractGeminiErrorMessage(string $body, int $status): string
    {
        $decoded = json_decode($body, true);
        if (isset($decoded['error']['message']) && $decoded['error']['message'] !== '') {
            return $decoded['error']['message'];
        }

        return "HTTP {$status}";
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $clean = trim($text);
        if ($clean === '') {
            return null;
        }

        $clean = preg_replace('/^```(?:json)?\\s*/i', '', $clean);
        $clean = preg_replace('/\\s*```$/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $firstBrace = strpos($clean, '{');
        $firstBracket = strpos($clean, '[');
        if ($firstBrace === false && $firstBracket === false) {
            return null;
        }

        if ($firstBrace === false || ($firstBracket !== false && $firstBracket < $firstBrace)) {
            $start = $firstBracket;
            $end = strrpos($clean, ']');
        } else {
            $start = $firstBrace;
            $end = strrpos($clean, '}');
        }

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = substr($clean, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
