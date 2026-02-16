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
            ],
        ]);
    }

    private function fetchPrescriptions(int $petId): array
    {
        if (!Schema::hasTable('prescriptions')) {
            return ['items' => [], 'medications' => [], 'next_follow_up' => null, 'protocol' => null];
        }

        $items = DB::table('prescriptions')
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'diagnosis',
                'disease_name',
                'medications_json',
                'diagnosis_status',
                'treatment_plan',
                'home_care',
                'follow_up_date',
                'follow_up_type',
                'follow_up_notes',
                'visit_notes',
                'exam_notes',
                'case_severity',
                'visit_category',
                'content_html',
                'image_path',
                'created_at',
            ])
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
