<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Support\GeminiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicPrescriptionDiagnosisUsersController extends Controller
{
    private const HIDDEN_PRESCRIPTION_IDS = [
        399, 400, 401, 403, 312, 309, 308, 169, 162, 161, 160, 152, 151, 121, 120,
    ];

    public function __invoke(Request $request)
    {
        $hasRequiredTables = Schema::hasTable('prescriptions') && Schema::hasTable('users');
        $diagnosisColumn = $this->diagnosisColumn();
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('prescriptions', 'user_id')
            && $diagnosisColumn !== null;

        $prescriptions = collect();
        $metrics = [
            'prescriptions' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $query = $this->baseQuery($diagnosisColumn);

            $metrics = [
                'prescriptions' => (clone $query)->count(),
                'unique_users' => (clone $query)->distinct('user_id')->count('user_id'),
            ];

            $prescriptions = $query
                ->orderByDesc(Schema::hasColumn('prescriptions', 'created_at') ? 'created_at' : 'id')
                ->orderByDesc('id')
                ->paginate(100)
                ->withQueryString();
        }

        return view('public.prescription-diagnosis-users', [
            'prescriptions' => $prescriptions,
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
            'diagnosisColumn' => $diagnosisColumn,
        ]);
    }

    public function aiAnalysis(Request $request, Prescription $prescription)
    {
        $diagnosisColumn = $this->diagnosisColumn();
        if ($diagnosisColumn === null || in_array((int) $prescription->id, self::HIDDEN_PRESCRIPTION_IDS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription is not available for this report.',
            ], 404);
        }

        $prescription->loadMissing([
            'user' => fn ($query) => $query->select($this->userColumns()),
            'pet' => fn ($query) => $query->select($this->petColumns()),
            'doctor' => fn ($query) => $query->select($this->doctorColumns()),
        ]);

        $diagnosis = trim((string) ($prescription->{$diagnosisColumn} ?? ''));
        if ($diagnosis === '' || $prescription->user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription diagnosis or user is missing.',
            ], 404);
        }

        $reportedSymptom = $this->reportedSymptomFor($prescription);
        if ($reportedSymptom === '') {
            return response()->json([
                'success' => true,
                'data' => [
                    'ai_summary' => 'No reported symptom is available for this prescription, so AI symptom analysis cannot be generated.',
                    'confidence' => 'none',
                    'comparison' => 'Doctor diagnosis is present, but there is no reported symptom to compare against.',
                    'basis' => ['Missing pets.reported_symptom for the related pet.'],
                    'model' => null,
                ],
            ]);
        }

        $cacheKey = 'prescription_diagnosis_users.ai_analysis.' . sha1(json_encode([
            'version' => 1,
            'prescription_id' => $prescription->id,
            'diagnosis' => $diagnosis,
            'reported_symptom' => $reportedSymptom,
        ]));

        $payload = Cache::remember($cacheKey, now()->addDay(), function () use ($prescription, $diagnosis, $reportedSymptom) {
            return $this->generateAiAnalysis($prescription, $diagnosis, $reportedSymptom);
        });

        return response()->json($payload, ($payload['success'] ?? false) ? 200 : 502);
    }

    private function baseQuery(string $diagnosisColumn)
    {
        $query = Prescription::query()
            ->select($this->prescriptionColumns($diagnosisColumn))
            ->whereNotIn('id', self::HIDDEN_PRESCRIPTION_IDS)
            ->whereNotNull('user_id')
            ->whereNotNull($diagnosisColumn)
            ->where($diagnosisColumn, '!=', '')
            ->whereHas('user')
            ->with([
                'user' => fn ($query) => $query->select($this->userColumns()),
            ]);

        if (Schema::hasTable('pets') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $query->with(['pet' => fn ($query) => $query->select($this->petColumns())]);
        }

        if (Schema::hasTable('doctors') && Schema::hasColumn('prescriptions', 'doctor_id')) {
            $query->with(['doctor' => fn ($query) => $query->select($this->doctorColumns())]);
        }

        return $query;
    }

    private function diagnosisColumn(): ?string
    {
        if (! Schema::hasTable('prescriptions')) {
            return null;
        }

        foreach (['diagnosis', 'diagnosys'] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function prescriptionColumns(string $diagnosisColumn): array
    {
        $columns = ['id', 'user_id', $diagnosisColumn];
        foreach ([
            'pet_id',
            'doctor_id',
            'call_session',
            'disease_name',
            'diagnosis_status',
            'created_at',
        ] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function userColumns(): array
    {
        $columns = ['id'];
        foreach (['name', 'email', 'phone', 'city'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function petColumns(): array
    {
        $columns = ['id'];
        foreach (['user_id', 'name', 'breed', 'pet_type', 'type', 'pet_age', 'pet_gender', 'reported_symptom'] as $column) {
            if (Schema::hasColumn('pets', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function doctorColumns(): array
    {
        $columns = ['id'];
        foreach (['doctor_name', 'doctor_email', 'doctor_mobile', 'degree'] as $column) {
            if (Schema::hasColumn('doctors', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function reportedSymptomFor(Prescription $prescription): string
    {
        if ($prescription->pet && Schema::hasColumn('pets', 'reported_symptom')) {
            return trim((string) ($prescription->pet->reported_symptom ?? ''));
        }

        return '';
    }

    private function generateAiAnalysis(Prescription $prescription, string $diagnosis, string $reportedSymptom): array
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Gemini API key is not configured.',
            ];
        }

        $model = 'gemini-2.5-flash';
        $prompt = $this->aiAnalysisPrompt($prescription, $diagnosis, $reportedSymptom);
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'topP' => 0.8,
                'topK' => 32,
                'maxOutputTokens' => 900,
                'responseMimeType' => 'application/json',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            Log::warning('prescription_diagnosis_users.ai_analysis.gemini_curl_failed', [
                'prescription_id' => $prescription->id,
                'error' => $err,
                'info' => $info,
            ]);

            return [
                'success' => false,
                'message' => "Gemini {$model} cURL error: {$err}",
            ];
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            Log::warning('prescription_diagnosis_users.ai_analysis.gemini_http_failed', [
                'prescription_id' => $prescription->id,
                'status' => $http,
                'body' => substr($resp, 0, 500),
            ]);

            return [
                'success' => false,
                'message' => "Gemini {$model} returned HTTP {$http}.",
            ];
        }

        $json = json_decode($resp, true);
        $text = trim((string) ($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        $decoded = $this->decodeGeminiJson($text);

        return [
            'success' => true,
            'data' => $this->normalizeAiAnalysis($decoded, $diagnosis, $reportedSymptom),
        ];
    }

    private function aiAnalysisPrompt(Prescription $prescription, string $diagnosis, string $reportedSymptom): string
    {
        $pet = $prescription->pet;

        return <<<PROMPT
You are a veterinary clinical assistant. Analyze whether the reported symptom supports, partially supports, or does not support the doctor's prescription diagnosis.

Context:
- prescription_id: {$prescription->id}
- pet_name: {$pet?->name}
- pet_type: {$pet?->pet_type}
- pet_breed: {$pet?->breed}
- pet_age: {$pet?->pet_age}
- pet_gender: {$pet?->pet_gender}
- reported_symptom: {$reportedSymptom}
- doctor_diagnosis: {$diagnosis}

Return valid JSON only:
{
  "ai_summary": "specific clinical interpretation based on reported symptom",
  "confidence": "low|medium|high",
  "comparison": "how reported symptom compares with doctor diagnosis",
  "basis": ["short evidence points"]
}

Rules:
- Do not output N/A.
- If reported symptom is vague, say it is insufficient and explain what is missing.
- Do not invent symptoms not present in reported_symptom.
- Keep it concise and useful for internal review.
PROMPT;
    }

    private function normalizeAiAnalysis(?array $decoded, string $diagnosis, string $reportedSymptom): array
    {
        $summary = trim((string) ($decoded['ai_summary'] ?? $decoded['summary'] ?? ''));
        if ($summary === '' || in_array(strtolower($summary), ['n/a', 'na', 'unknown'], true)) {
            $summary = "Reported symptom '{$reportedSymptom}' should be clinically reviewed against doctor diagnosis '{$diagnosis}'.";
        }

        $confidence = strtolower(trim((string) ($decoded['confidence'] ?? 'low')));
        if (! in_array($confidence, ['low', 'medium', 'high'], true)) {
            $confidence = 'low';
        }

        $comparison = trim((string) ($decoded['comparison'] ?? ''));
        if ($comparison === '') {
            $comparison = "Doctor diagnosis: {$diagnosis}. Reported symptom: {$reportedSymptom}.";
        }

        $basis = $decoded['basis'] ?? [];
        if (! is_array($basis)) {
            $basis = [];
        }
        $basis = array_values(array_filter(array_map(fn ($item) => trim((string) $item), $basis)));
        if (empty($basis)) {
            $basis = [
                "Reported symptom: {$reportedSymptom}",
                "Doctor diagnosis: {$diagnosis}",
            ];
        }

        return [
            'ai_summary' => $summary,
            'confidence' => $confidence,
            'comparison' => $comparison,
            'basis' => $basis,
            'model' => 'gemini-2.5-flash',
        ];
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', (string) $text);
            $text = trim((string) $text);
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }
}
